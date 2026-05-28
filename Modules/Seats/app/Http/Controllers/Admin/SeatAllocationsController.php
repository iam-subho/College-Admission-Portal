<?php

namespace Modules\Seats\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Models\AdmissionRound;
use Modules\Admissions\Models\ProgramReservation;
use Modules\Seats\Models\SeatAllocation;
use Modules\Seats\Services\SeatAllocator;

class SeatAllocationsController extends Controller
{
    public function index(): Response
    {
        $rounds = AdmissionRound::query()
            ->with([
                'program:id,code,name,type,intake_capacity',
                'session:id,code',
                'meritList:id,admission_round_id,status,total_candidates',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(function (AdmissionRound $r) {
                $allocs = SeatAllocation::where('admission_round_id', $r->id)->get();

                return [
                    'id' => $r->id,
                    'round_number' => $r->round_number,
                    'name' => $r->name,
                    'status' => $r->status,
                    'program' => $r->program,
                    'session' => $r->session,
                    'merit_list_status' => $r->meritList?->status,
                    'acceptance_window_days' => $r->acceptance_window_days,
                    'allotment_generated_at' => $r->allotment_generated_at,
                    'counts' => [
                        'allotted' => $allocs->where('status', SeatAllocation::STATUS_ALLOTTED)->count(),
                        'accepted' => $allocs->where('status', SeatAllocation::STATUS_ACCEPTED)->count(),
                        'admitted' => $allocs->where('status', SeatAllocation::STATUS_ADMITTED)->count(),
                        'declined' => $allocs->where('status', SeatAllocation::STATUS_DECLINED)->count(),
                        'expired' => $allocs->where('status', SeatAllocation::STATUS_EXPIRED)->count(),
                        'withdrawn' => $allocs->where('status', SeatAllocation::STATUS_WITHDRAWN)->count(),
                        'total' => $allocs->count(),
                    ],
                ];
            });

        return Inertia::render('Admin/SeatAllocations', [
            'rounds' => $rounds,
        ]);
    }

    public function show(AdmissionRound $round): Response
    {
        $round->load(['program:id,code,name,type,intake_capacity', 'session:id,code,name']);

        $reservations = ProgramReservation::query()
            ->where('program_id', $round->program_id)
            ->where('academic_session_id', $round->academic_session_id)
            ->with('category:id,code,name,is_horizontal')
            ->get()
            ->reject(fn ($r) => $r->category?->is_horizontal)
            ->values()
            ->map(function ($r) use ($round) {
                $allocs = SeatAllocation::where('admission_round_id', $round->id)
                    ->where('reservation_category_id', $r->reservation_category_id)
                    ->get();

                return [
                    'category' => $r->category,
                    'seats' => (int) $r->seats,
                    'allotted' => $allocs->where('status', SeatAllocation::STATUS_ALLOTTED)->count(),
                    'accepted' => $allocs->where('status', SeatAllocation::STATUS_ACCEPTED)->count(),
                    'admitted' => $allocs->where('status', SeatAllocation::STATUS_ADMITTED)->count(),
                    'declined' => $allocs->where('status', SeatAllocation::STATUS_DECLINED)->count(),
                    'expired' => $allocs->where('status', SeatAllocation::STATUS_EXPIRED)->count(),
                    'open_count' => $allocs->whereIn('status', [SeatAllocation::STATUS_ALLOTTED, SeatAllocation::STATUS_ACCEPTED, SeatAllocation::STATUS_ADMITTED])->count(),
                ];
            });

        $allocations = SeatAllocation::query()
            ->where('admission_round_id', $round->id)
            ->with([
                'application:id,application_number,student_id',
                'application.student.user:id,name,email,mobile',
                'category:id,code,name',
                'admittedBy:id,name',
            ])
            ->orderBy('reservation_category_id')
            ->orderBy('rank_at_allotment')
            ->get()
            ->map(fn (SeatAllocation $a) => [
                'id' => $a->id,
                'status' => $a->status,
                'source' => $a->source,
                'application_number' => $a->application?->application_number,
                'applicant_name' => $a->application?->student?->user?->name,
                'applicant_email' => $a->application?->student?->user?->email,
                'category_code' => $a->category?->code,
                'rank' => $a->rank_at_allotment,
                'category_rank' => $a->category_rank_at_allotment,
                'allotted_at' => $a->allotted_at,
                'expires_at' => $a->expires_at,
                'decided_at' => $a->decided_at,
                'admitted_at' => $a->admitted_at,
                'is_expired' => $a->isExpired(),
                'remark' => $a->audit_remark,
            ]);

        return Inertia::render('Admin/SeatAllocationsDetail', [
            'round' => $round,
            'reservations' => $reservations,
            'allocations' => $allocations,
        ]);
    }

    public function generate(AdmissionRound $round, SeatAllocator $allocator): RedirectResponse
    {
        $result = $allocator->generate($round);

        return back()->with('flash', [
            'success' => "Allotment generated — {$result['allotted']} new seat(s) offered.",
        ]);
    }

    public function lockAllotment(AdmissionRound $round): RedirectResponse
    {
        $round->forceFill(['allotment_locked_at' => now(), 'status' => AdmissionRound::STATUS_LOCKED])->save();

        return back()->with('flash', ['success' => 'Allotment locked — no further generations or rollovers.']);
    }
}
