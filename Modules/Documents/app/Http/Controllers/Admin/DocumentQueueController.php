<?php

namespace Modules\Documents\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Documents\Actions\ApproveDocumentAction;
use Modules\Documents\Actions\RejectDocumentAction;
use Modules\Documents\Models\UploadedDocument;

class DocumentQueueController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string'],
            'source' => ['nullable', 'string'],
            'q' => ['nullable', 'string'],
        ]);

        $query = UploadedDocument::query()
            ->with([
                'type:id,code,label',
                'student.user:id,name,email,mobile',
                'application:id,application_number,program_id',
                'application.program:id,code,name',
            ])
            ->orderByDesc('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }
        if (! empty($filters['q'])) {
            $term = "%{$filters['q']}%";
            $query->where(function ($q) use ($term) {
                $q->whereHas('application', fn ($a) => $a->where('application_number', 'like', $term))
                    ->orWhereHas('student.user', fn ($u) => $u->where('email', 'like', $term)->orWhere('name', 'like', $term));
            });
        }

        return Inertia::render('Admin/DocumentQueue', [
            'documents' => $query->paginate(25)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function approve(Request $request, UploadedDocument $document, ApproveDocumentAction $action): RedirectResponse
    {
        $data = $request->validate([
            'remark' => ['nullable', 'string', 'max:500'],
        ]);

        $action->execute($document, $request->user(), $data['remark'] ?? null);
        event(new \Modules\Notifications\Events\DocumentApprovedEvent($document->fresh()));

        return back()->with('flash', ['success' => 'Document approved.']);
    }

    public function reject(Request $request, UploadedDocument $document, RejectDocumentAction $action): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $action->execute($document, $request->user(), $data['reason']);
        event(new \Modules\Notifications\Events\DocumentRejectedEvent($document->fresh(), $data['reason']));

        return back()->with('flash', ['success' => 'Document rejected with reason.']);
    }

    public function bulkApprove(Request $request, ApproveDocumentAction $action): RedirectResponse
    {
        $data = $request->validate([
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer', 'exists:uploaded_documents,id'],
            'remark' => ['nullable', 'string', 'max:500'],
        ]);

        $docs = UploadedDocument::whereIn('id', $data['document_ids'])->get();
        foreach ($docs as $doc) {
            $action->execute($doc, $request->user(), $data['remark'] ?? null);
        }

        return back()->with('flash', ['success' => count($docs).' document(s) approved.']);
    }
}
