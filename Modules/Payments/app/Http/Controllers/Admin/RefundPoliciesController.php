<?php

namespace Modules\Payments\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Models\AcademicSession;
use Modules\Payments\Models\RefundPolicy;

class RefundPoliciesController extends Controller
{
    public function index(): Response
    {
        $policies = RefundPolicy::with('session:id,code,name,commencement_date')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Admin/RefundPolicies', [
            'policies' => $policies,
            'sessions' => AcademicSession::orderByDesc('is_active')->get(['id', 'code', 'name', 'commencement_date', 'is_active']),
            'fee_types' => [
                RefundPolicy::FEE_TYPE_APPLICATION => 'Application Fee',
                RefundPolicy::FEE_TYPE_ADMISSION => 'Admission Fee',
            ],
            'ugc_template' => $this->ugcSlabsTemplate(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        // Deactivate existing active policy for the same (session, fee_type).
        RefundPolicy::where('academic_session_id', $data['academic_session_id'])
            ->where('fee_type', $data['fee_type'])
            ->where('is_active', true)
            ->update(['is_active' => false]);

        RefundPolicy::create($data + ['is_active' => true]);

        return back()->with('flash', ['success' => 'Refund policy saved.']);
    }

    public function update(Request $request, RefundPolicy $policy): RedirectResponse
    {
        $data = $this->validatePayload($request);

        $policy->update($data);

        return back()->with('flash', ['success' => 'Refund policy updated.']);
    }

    public function destroy(RefundPolicy $policy): RedirectResponse
    {
        $policy->delete();

        return back()->with('flash', ['success' => 'Refund policy deleted.']);
    }

    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'academic_session_id' => ['required', 'exists:academic_sessions,id'],
            'fee_type' => ['required', 'in:application,admission'],
            'name' => ['required', 'string', 'max:100'],
            'slabs' => ['required', 'array', 'min:1'],
            'slabs.*.from_days' => ['nullable', 'integer', 'min:-365', 'max:365'],
            'slabs.*.to_days' => ['nullable', 'integer', 'min:-365', 'max:365'],
            'slabs.*.refund_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'slabs.*.label' => ['nullable', 'string', 'max:80'],
            'deduction_cap' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);
    }

    protected function ugcSlabsTemplate(): array
    {
        return [
            ['from_days' => null, 'to_days' => 30, 'refund_pct' => 100, 'label' => 'More than 30 days before session start'],
            ['from_days' => 30, 'to_days' => 15, 'refund_pct' => 80, 'label' => '15 to 30 days before session start'],
            ['from_days' => 15, 'to_days' => 0, 'refund_pct' => 50, 'label' => '0 to 15 days before session start'],
            ['from_days' => 0, 'to_days' => null, 'refund_pct' => 0, 'label' => 'On or after session start'],
        ];
    }
}
