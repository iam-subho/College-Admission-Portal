<?php

namespace Modules\Documents\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Admissions\Models\Application;
use Modules\Documents\Models\DocumentType;
use Modules\Documents\Models\UploadedDocument;
use Modules\Documents\Services\DocumentRepository;
use Modules\Students\Models\Student;

class UploadDocumentAction
{
    public function __construct(protected DocumentRepository $repo) {}

    public function execute(
        Student $student,
        DocumentType $type,
        UploadedFile $file,
        ?Application $application = null,
        ?string $disk = null,
    ): UploadedDocument {
        $this->validate($type, $file);

        $disk ??= config('docs.default_disk', 'documents_local');

        return DB::transaction(function () use ($student, $type, $file, $application, $disk) {
            $filename = $this->generateFilename($file);
            $relative = "students/{$student->id}/{$type->code}/{$filename}";

            $stream = fopen($file->getRealPath(), 'rb');
            \Illuminate\Support\Facades\Storage::disk($disk)->put($relative, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            $checksum = hash_file('sha256', $file->getRealPath());

            $existing = UploadedDocument::where([
                'student_id' => $student->id,
                'application_id' => $application?->id,
                'document_type_id' => $type->id,
            ])->first();

            if ($existing) {
                $this->repo->delete($existing);

                $existing->update([
                    'disk' => $disk,
                    'path' => $relative,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType() ?? 'application/octet-stream',
                    'size_bytes' => $file->getSize(),
                    'checksum_sha256' => $checksum,
                    'source' => UploadedDocument::SOURCE_MANUAL,
                    'status' => UploadedDocument::STATUS_PENDING,
                    'rejection_reason' => null,
                    'status_changed_at' => now(),
                ]);

                return $existing->fresh();
            }

            return UploadedDocument::create([
                'student_id' => $student->id,
                'application_id' => $application?->id,
                'document_type_id' => $type->id,
                'disk' => $disk,
                'path' => $relative,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?? 'application/octet-stream',
                'size_bytes' => $file->getSize(),
                'checksum_sha256' => $checksum,
                'source' => UploadedDocument::SOURCE_MANUAL,
                'status' => UploadedDocument::STATUS_PENDING,
                'status_changed_at' => now(),
            ]);
        });
    }

    protected function validate(DocumentType $type, UploadedFile $file): void
    {
        $allowed = $type->defaultAllowedMimes();
        if (! in_array($file->getMimeType(), $allowed, true)) {
            throw ValidationException::withMessages([
                'file' => "Mime '{$file->getMimeType()}' not allowed for {$type->code}. Allowed: ".implode(', ', $allowed),
            ]);
        }

        $maxBytes = ($type->max_size_kb ?: 2048) * 1024;
        if ($file->getSize() > $maxBytes) {
            throw ValidationException::withMessages([
                'file' => "File exceeds {$type->max_size_kb} KB limit.",
            ]);
        }
    }

    protected function generateFilename(UploadedFile $file): string
    {
        $ext = $file->getClientOriginalExtension() ?: 'bin';

        return Str::uuid().'.'.$ext;
    }
}
