<?php

namespace Modules\Seats\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Models\Application;
use Modules\Payments\Services\AdmissionFeeResolver;
use Modules\Seats\Models\SeatAcceptance;
use Modules\Seats\Models\SeatAllocation;
use Modules\Seats\Services\SeatAllocator;

class AllotmentController extends Controller
{
    public function show(Request $request, Application $application, AdmissionFeeResolver $feeResolver): Response|RedirectResponse
    {
        $this->authorizeOwnership($application, $request);

        $allocation = SeatAllocation::where('application_id', $application->id)
            ->latest('id')
            ->with(['round.program:id,code,name,type', 'round.session:id,code,name', 'category:id,code,name', 'admissionFeeOrder'])
            ->first();

        $fee = $feeResolver->admissionFeeFor($application);

        return Inertia::render('Student/AllotmentLetter', [
            'application' => $application->load(['program:id,code,name,type', 'session:id,code,name']),
            'allocation' => $allocation,
            'admission_fee' => $fee,
            'can_accept' => $allocation
                && $allocation->status === SeatAllocation::STATUS_ALLOTTED
                && ! $allocation->isExpired(),
        ]);
    }

    public function accept(Request $request, SeatAllocation $allocation, SeatAllocator $allocator): RedirectResponse
    {
        $this->authorizeAllocationOwner($allocation, $request);

        if ($allocation->status !== SeatAllocation::STATUS_ALLOTTED) {
            return back()->with('flash', ['error' => 'This allocation is no longer in an acceptable state.']);
        }
        if ($allocation->isExpired()) {
            return back()->with('flash', ['error' => 'Acceptance window has expired. Please contact admissions.']);
        }

        $allocator->recordAction(
            $allocation,
            SeatAcceptance::ACTION_ACCEPT,
            $request->user()->id,
        );

        return redirect()->route('student.allotment.show', $allocation->application_id)
            ->with('flash', ['success' => 'Seat accepted. Please pay the admission fee to confirm your admission.']);
    }

    public function decline(Request $request, SeatAllocation $allocation, SeatAllocator $allocator): RedirectResponse
    {
        $this->authorizeAllocationOwner($allocation, $request);

        if (! in_array($allocation->status, [SeatAllocation::STATUS_ALLOTTED, SeatAllocation::STATUS_ACCEPTED], true)) {
            return back()->with('flash', ['error' => 'This allocation cannot be declined in its current state.']);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $allocator->recordAction(
            $allocation,
            SeatAcceptance::ACTION_DECLINE,
            $request->user()->id,
            $data['reason'],
        );

        return redirect()->route('student.applications.index')
            ->with('flash', ['success' => 'Seat declined. The next waitlisted candidate has been notified.']);
    }

    protected function authorizeOwnership(Application $application, Request $request): void
    {
        abort_unless(
            $application->student?->user_id === $request->user()->id,
            403,
            'Not your application.',
        );
    }

    protected function authorizeAllocationOwner(SeatAllocation $allocation, Request $request): void
    {
        $app = $allocation->application ?? $allocation->load('application.student')->application;
        abort_unless(
            $app?->student?->user_id === $request->user()->id,
            403,
            'Not your allocation.',
        );
    }
}
