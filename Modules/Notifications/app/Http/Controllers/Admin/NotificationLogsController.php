<?php

namespace Modules\Notifications\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Notifications\Models\NotificationLog;

class NotificationLogsController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->validate([
            'event' => ['nullable', 'string'],
            'channel' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        $query = NotificationLog::query()
            ->with('user:id,name,email')
            ->orderByDesc('id');

        if (! empty($filter['event'])) {
            $query->where('event', $filter['event']);
        }
        if (! empty($filter['channel'])) {
            $query->where('channel', $filter['channel']);
        }
        if (! empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        return Inertia::render('Admin/NotificationLogs', [
            'logs' => $query->paginate(50)->withQueryString(),
            'filters' => $filter,
        ]);
    }
}
