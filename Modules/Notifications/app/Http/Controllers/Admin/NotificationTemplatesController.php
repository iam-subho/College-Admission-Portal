<?php

namespace Modules\Notifications\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Notifications\Models\NotificationTemplate;

class NotificationTemplatesController extends Controller
{
    /**
     * Known event keys — mirrors the NotifiableEvent classes. Used to scope the
     * admin form so admin can only target known events.
     */
    public const EVENT_KEYS = [
        'application_submitted',
        'application_fee_received',
        'admission_fee_received',
        'admit_card_released',
        'document_approved',
        'document_rejected',
        'eligibility_overridden',
        'merit_list_published',
        'seat_allotted',
        'acceptance_reminder',
        'admission_confirmed',
        'withdrawal_approved',
        'refund_completed',
    ];

    public function index(): Response
    {
        return Inertia::render('Admin/NotificationTemplates', [
            'templates' => NotificationTemplate::orderBy('event')->orderBy('channel')->get(),
            'event_keys' => self::EVENT_KEYS,
            'channels' => NotificationTemplate::CHANNELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        NotificationTemplate::create($data + ['is_active' => true]);

        return back()->with('flash', ['success' => 'Template created.']);
    }

    public function update(Request $request, NotificationTemplate $template): RedirectResponse
    {
        $data = $this->validatePayload($request, $template);
        $template->update($data);

        return back()->with('flash', ['success' => 'Template updated.']);
    }

    public function toggle(NotificationTemplate $template): RedirectResponse
    {
        $template->update(['is_active' => ! $template->is_active]);

        return back()->with('flash', ['success' => 'Template '.($template->is_active ? 'activated' : 'deactivated').'.']);
    }

    public function destroy(NotificationTemplate $template): RedirectResponse
    {
        $template->delete();

        return back()->with('flash', ['success' => 'Template deleted.']);
    }

    protected function validatePayload(Request $request, ?NotificationTemplate $template = null): array
    {
        return $request->validate([
            'event' => ['required', Rule::in(self::EVENT_KEYS)],
            'channel' => ['required', Rule::in(NotificationTemplate::CHANNELS)],
            'subject' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'dlt_template_id' => ['nullable', 'string', 'max:64'],
            'whatsapp_template_name' => ['nullable', 'string', 'max:64'],
        ]);
    }
}
