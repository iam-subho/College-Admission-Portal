<?php

namespace Modules\Admissions\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Actions\ActivateSessionAction;
use Modules\Admissions\Models\AcademicSession;

class SessionsController extends Controller
{
    public function index(): Response
    {
        $sessions = AcademicSession::orderByDesc('is_active')
            ->orderByDesc('commencement_date')
            ->get();

        return Inertia::render('Admin/Sessions', [
            'sessions' => $sessions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        AcademicSession::create($data + [
            'is_active' => false,
            'status' => AcademicSession::STATUS_PLANNING,
        ]);

        return back()->with('flash', ['success' => 'Session created.']);
    }

    public function update(Request $request, AcademicSession $session): RedirectResponse
    {
        $data = $this->validatePayload($request, $session);

        $session->update($data);

        return back()->with('flash', ['success' => 'Session updated.']);
    }

    protected function validatePayload(Request $request, ?AcademicSession $session = null): array
    {
        $codeRule = $session
            ? 'unique:academic_sessions,code,'.$session->id
            : 'unique:academic_sessions,code';

        $data = $request->validate([
            'code' => ['required', 'string', 'max:16', $codeRule],
            'name' => ['required', 'string', 'max:120'],
            'commencement_date' => ['nullable', 'date'],
            'application_open_date' => ['nullable', 'date'],
            'application_close_date' => ['nullable', 'date', 'after_or_equal:application_open_date'],
            'payment_mode' => ['sometimes', 'in:per_programme,one_time'],
            'application_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['payment_mode'] ??= AcademicSession::PAYMENT_MODE_PER_PROGRAMME;

        if ($data['payment_mode'] === AcademicSession::PAYMENT_MODE_ONE_TIME
            && ($data['application_fee'] ?? null) === null) {
            abort(422, 'application_fee is required when payment_mode is one_time.');
        }

        return $data;
    }

    public function activate(AcademicSession $session, ActivateSessionAction $action): RedirectResponse
    {
        $action->execute($session);

        return back()->with('flash', ['success' => "Session {$session->code} activated."]);
    }

    public function archive(AcademicSession $session): RedirectResponse
    {
        $session->forceFill([
            'is_active' => false,
            'status' => AcademicSession::STATUS_ARCHIVED,
        ])->save();

        return back()->with('flash', ['success' => "Session {$session->code} archived."]);
    }

    public function destroy(AcademicSession $session): RedirectResponse
    {
        $session->delete();

        return back()->with('flash', ['success' => 'Session removed.']);
    }
}
