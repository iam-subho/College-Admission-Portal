<?php

namespace Modules\Seats\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Models\AdmissionRound;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\ReservationCategory;
use Modules\Seats\Models\SeatAllocation;
use Modules\Seats\Services\SeatAllocator;

class SpotAdmissionsController extends Controller
{
    public function index(): Response
    {
        // Show rounds that are open or merit_published with at least one vacant seat.
        $rounds = AdmissionRound::query()
            ->with(['program:id,code,name,type,intake_capacity', 'session:id,code'])
            ->whereIn('status', [
                AdmissionRound::STATUS_MERIT_PUBLISHED,
                AdmissionRound::STATUS_OPEN,
                AdmissionRound::STATUS_CLOSED,
            ])
            ->orderByDesc('id')
            ->get();

        // Eligible applications for spot admission: submitted+paid, no existing allocation in any round.
        $allocatedAppIds = SeatAllocation::pluck('application_id')->all();
        $eligibleApps = Application::query()
            ->where('status', Application::STATUS_SUBMITTED)
            ->whereIn('payment_status', [
                Application::PAYMENT_PAID,
                Application::PAYMENT_COVERED,
                Application::PAYMENT_NOT_REQUIRED,
            ])
            ->whereNotIn('id', $allocatedAppIds)
            ->with(['student.user:id,name,email,mobile', 'student.category:id,code,name', 'program:id,code,name'])
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(fn ($a) => [
                'value' => $a->id,
                'label' => "{$a->application_number} · {$a->student?->user?->name} · {$a->program?->code}",
                'sub' => $a->student?->user?->email,
                'group' => $a->program?->code,
                'program_id' => $a->program_id,
                'category_id' => $a->student?->reservation_category_id,
                'category_code' => $a->student?->category?->code,
            ]);

        return Inertia::render('Admin/SpotAdmissions', [
            'rounds' => $rounds,
            'eligible_applications' => $eligibleApps,
            'categories' => ReservationCategory::where('is_active', true)
                ->where('is_horizontal', false)
                ->orderBy('ordering')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request, SeatAllocator $allocator): RedirectResponse
    {
        $data = $request->validate([
            'admission_round_id' => ['required', 'exists:admission_rounds,id'],
            'application_id' => ['required', 'exists:applications,id'],
            'reservation_category_id' => ['nullable', 'exists:reservation_categories,id'],
            'remark' => ['nullable', 'string', 'max:500'],
        ]);

        $round = AdmissionRound::findOrFail($data['admission_round_id']);
        $application = Application::findOrFail($data['application_id']);

        abort_unless($application->program_id === $round->program_id, 422, 'Application programme does not match round programme.');

        $alloc = $allocator->spotAllot(
            $round,
            $application,
            $data['reservation_category_id'] ?? null,
            $request->user()->id,
            $data['remark'] ?? null,
        );

        return back()->with('flash', [
            'success' => "Spot allotted seat #{$alloc->id} to {$application->application_number}.",
        ]);
    }
}
