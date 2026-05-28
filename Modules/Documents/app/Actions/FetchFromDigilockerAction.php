<?php

namespace Modules\Documents\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Admissions\Models\Application;
use Modules\Documents\Models\DocumentType;
use Modules\Documents\Models\UploadedDocument;
use Modules\Documents\Services\Digilocker\DigilockerManager;
use Modules\Documents\Services\DocumentRepository;
use Modules\Students\Models\Student;

class FetchFromDigilockerAction
{
    public function __construct(
        protected DigilockerManager $digilocker,
        protected DocumentRepository $repo,
    ) {}

    public function execute(
        Student $student,
        User $user,
        DocumentType $type,
        ?Application $application = null,
        ?string $disk = null,
    ): UploadedDocument {
        $result = $this->digilocker->driver()->fetchDocument($user, $type);

        $disk ??= config('docs.default_disk', 'documents_local');

        return DB::transaction(function () use ($student, $type, $application, $disk, $result) {
            $ext = match ($result['mime']) {
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                default => 'bin',
            };
            $filename = Str::uuid().'.'.$ext;
            $relative = "students/{$student->id}/{$type->code}/{$filename}";

            Storage::disk($disk)->put($relative, $result['bytes']);
            $checksum = hash('sha256', $result['bytes']);

            $existing = UploadedDocument::where([
                'student_id' => $student->id,
                'application_id' => $application?->id,
                'document_type_id' => $type->id,
            ])->first();

            $payload = [
                'student_id' => $student->id,
                'application_id' => $application?->id,
                'document_type_id' => $type->id,
                'disk' => $disk,
                'path' => $relative,
                'original_name' => "{$type->code}-digilocker.{$ext}",
                'mime' => $result['mime'],
                'size_bytes' => strlen($result['bytes']),
                'checksum_sha256' => $checksum,
                'source' => UploadedDocument::SOURCE_DIGILOCKER,
                'digilocker_uri' => $result['uri'] ?? null,
                'digilocker_issued_by' => $result['issuer'] ?? null,
                'digilocker_pulled_at' => now(),
                'status' => UploadedDocument::STATUS_PENDING,
                'rejection_reason' => null,
                'status_changed_at' => now(),
            ];

            if ($existing) {
                $this->repo->delete($existing);
                $existing->update($payload);

                return $existing->fresh();
            }

            return UploadedDocument::create($payload);
        });
    }
}
