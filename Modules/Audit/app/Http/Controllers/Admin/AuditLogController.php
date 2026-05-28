<?php

namespace Modules\Audit\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->validate([
            'log_name' => ['nullable', 'string'],
            'event' => ['nullable', 'string'],
            'causer_email' => ['nullable', 'string'],
            'subject_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string'],
        ]);

        $query = Activity::query()
            ->with(['causer:id,name,email'])
            ->orderByDesc('id');

        if (! empty($filter['log_name'])) {
            $query->where('log_name', $filter['log_name']);
        }
        if (! empty($filter['event'])) {
            $query->where('event', $filter['event']);
        }
        if (! empty($filter['causer_email'])) {
            $email = $filter['causer_email'];
            $query->whereHas('causer', fn ($q) => $q->where('email', 'like', "%{$email}%"));
        }
        if (! empty($filter['subject_id'])) {
            $query->where('subject_id', $filter['subject_id']);
        }
        if (! empty($filter['from'])) {
            $query->whereDate('created_at', '>=', $filter['from']);
        }
        if (! empty($filter['to'])) {
            $query->whereDate('created_at', '<=', $filter['to']);
        }
        if (! empty($filter['q'])) {
            $query->where('description', 'like', '%'.$filter['q'].'%');
        }

        return Inertia::render('Admin/AuditLog', [
            'entries' => $query->paginate(50)->withQueryString()->through(fn (Activity $a) => [
                'id' => $a->id,
                'log_name' => $a->log_name,
                'event' => $a->event,
                'description' => $a->description,
                'causer_name' => $a->causer?->name,
                'causer_email' => $a->causer?->email,
                'subject_type' => $a->subject_type,
                'subject_id' => $a->subject_id,
                'changes' => $a->properties,
                'created_at' => $a->created_at,
            ]),
            'filter' => $filter,
            'log_names' => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name')->filter()->values(),
            'events' => ['created', 'updated', 'deleted', 'restored'],
        ]);
    }
}
