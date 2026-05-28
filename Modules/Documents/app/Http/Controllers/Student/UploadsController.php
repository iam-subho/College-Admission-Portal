<?php

namespace Modules\Documents\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Models\Application;
use Modules\Documents\Actions\FetchFromDigilockerAction;
use Modules\Documents\Actions\UploadDocumentAction;
use Modules\Documents\Models\DigilockerLink;
use Modules\Documents\Models\DocumentType;
use Modules\Documents\Models\UploadedDocument;
use Modules\Documents\Services\Digilocker\DigilockerException;
use Modules\Documents\Services\DocumentRepository;
use Modules\Students\Models\Student;

class UploadsController extends Controller
{
    public function index(Request $request): Response
    {
        $student = Student::firstOrCreate(['user_id' => $request->user()->id]);
        $applicationId = $request->input('application');

        $application = $applicationId
            ? Application::where('student_id', $student->id)->where('id', $applicationId)->first()
            : $student->applications()->latest('id')->first();

        $types = DocumentType::where('is_active', true)->orderBy('ordering')->get();

        $uploaded = UploadedDocument::where('student_id', $student->id)
            ->when($application, fn ($q) => $q->where('application_id', $application->id))
            ->get()
            ->keyBy('document_type_id');

        $items = $types->map(fn (DocumentType $t) => [
            'type' => $t,
            'document' => $uploaded->get($t->id),
        ]);

        $digilockerLinked = DigilockerLink::where('user_id', $request->user()->id)
            ->whereNotNull('linked_at')
            ->whereNull('revoked_at')
            ->exists();

        return Inertia::render('Student/Uploads', [
            'items' => $items,
            'application' => $application,
            'digilocker_linked' => $digilockerLinked,
        ]);
    }

    public function store(
        Request $request,
        DocumentType $type,
        UploadDocumentAction $action,
    ): RedirectResponse {
        $student = Student::firstOrCreate(['user_id' => $request->user()->id]);
        abort_if($student->profile_locked, 403, 'Profile locked. Contact admissions for changes.');

        $request->validate([
            'file' => ['required', 'file', 'max:'.($type->max_size_kb ?? 2048)],
            'application_id' => ['nullable', 'exists:applications,id'],
        ]);

        $application = $request->input('application_id')
            ? Application::where('student_id', $student->id)->find($request->input('application_id'))
            : null;

        $action->execute($student, $type, $request->file('file'), $application);

        app(\Modules\Audit\Services\DpdpConsentRecorder::class)->record(
            scope: \Modules\Users\Models\DpdpConsent::SCOPE_DOCUMENT_UPLOAD,
            userId: $request->user()->id,
            request: $request,
            metadata: [
                'document_type' => $type->code,
                'document_type_label' => $type->label,
                'application_id' => $application?->id,
            ],
        );

        return back()->with('flash', ['success' => "{$type->label} uploaded."]);
    }

    public function fetchDigilocker(
        Request $request,
        DocumentType $type,
        FetchFromDigilockerAction $action,
    ): RedirectResponse {
        $student = Student::firstOrCreate(['user_id' => $request->user()->id]);
        abort_if($student->profile_locked, 403, 'Profile locked. Contact admissions for changes.');

        $application = $request->input('application_id')
            ? Application::where('student_id', $student->id)->find($request->input('application_id'))
            : null;

        try {
            $action->execute($student, $request->user(), $type, $application);
        } catch (DigilockerException $e) {
            return back()->with('flash', [
                'error' => 'DigiLocker fetch failed: '.$e->getMessage().' Please upload manually instead.',
            ]);
        }

        return back()->with('flash', ['success' => "{$type->label} fetched from DigiLocker."]);
    }

    public function download(Request $request, UploadedDocument $document, DocumentRepository $repo): StreamedResponse
    {
        $this->authorizeAccess($request, $document);

        abort_unless($repo->exists($document), 404, 'File missing.');

        return $repo->disk($document)->download($document->path, $document->original_name);
    }

    public function preview(Request $request, UploadedDocument $document, DocumentRepository $repo): StreamedResponse
    {
        $this->authorizeAccess($request, $document);

        abort_unless($repo->exists($document), 404, 'File missing.');

        $disk = $repo->disk($document);

        return $disk->response($document->path, $document->original_name, [
            'Content-Type' => $document->mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($document->original_name).'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    protected function authorizeAccess(Request $request, UploadedDocument $document): void
    {
        $user = $request->user();
        $isOwner = $document->student?->user_id === $user->id;
        $isStaff = $user->hasAnyRole(['staff', 'admin', 'super_admin']);

        abort_unless($isOwner || $isStaff, 403, 'Not authorized to view this document.');
    }
}
