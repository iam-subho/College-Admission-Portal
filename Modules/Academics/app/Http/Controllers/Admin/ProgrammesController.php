<?php

namespace Modules\Academics\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academics\Models\Department;
use Modules\Academics\Models\Program;
use Modules\Academics\Models\ProgramAdmissionFee;
use Modules\Academics\Models\ProgramApplicationFee;
use Modules\Admissions\Actions\SyncReservationMatrixAction;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\ReservationCategory;

class ProgrammesController extends Controller
{
    public function index(): Response
    {
        $activeSession = AcademicSession::where('is_active', true)->first();

        $programs = Program::with([
            'department',
            'reservations.category',
            'applicationFeeOverrides.category:id,code,name',
            'admissionFeeOverrides.category:id,code,name',
        ])
            ->orderBy('name')
            ->get()
            ->map(function (Program $p) use ($activeSession) {
                $matrixSeats = $activeSession
                    ? $p->reservations
                        ->where('academic_session_id', $activeSession->id)
                        ->reject(fn ($r) => $r->category->is_horizontal)
                        ->sum('seats')
                    : 0;

                return [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'department' => $p->department?->name,
                    'department_id' => $p->department_id,
                    'type' => $p->type,
                    'duration_years' => $p->duration_years,
                    'total_credits' => $p->total_credits,
                    'intake_capacity' => $p->intake_capacity,
                    'application_fee' => $p->application_fee,
                    'admission_fee' => $p->admission_fee,
                    'fee_overrides' => $p->applicationFeeOverrides->map(fn (ProgramApplicationFee $o) => [
                        'id' => $o->id,
                        'category_id' => $o->reservation_category_id,
                        'category_code' => $o->category?->code,
                        'category_name' => $o->category?->name,
                        'application_fee' => $o->application_fee,
                    ]),
                    'admission_fee_overrides' => $p->admissionFeeOverrides->map(fn (ProgramAdmissionFee $o) => [
                        'id' => $o->id,
                        'category_id' => $o->reservation_category_id,
                        'category_code' => $o->category?->code,
                        'category_name' => $o->category?->name,
                        'admission_fee' => $o->admission_fee,
                    ]),
                    'reserved_seats' => $matrixSeats,
                    'is_active' => $p->is_active,
                ];
            });

        return Inertia::render('Admin/Programmes', [
            'programmes' => $programs,
            'departments' => Department::orderBy('name')->get(['id', 'code', 'name']),
            'categories' => ReservationCategory::orderBy('ordering')->get(),
            'active_session' => $activeSession,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:16', 'unique:programs,code'],
            'name' => ['required', 'string', 'max:150'],
            'department_id' => ['required', 'exists:departments,id'],
            'type' => ['required', 'in:UG,PG'],
            'duration_years' => ['required', 'integer', 'min:1', 'max:6'],
            'total_credits' => ['required', 'integer', 'min:1', 'max:300'],
            'intake_capacity' => ['required', 'integer', 'min:1', 'max:2000'],
            'application_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'admission_fee' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'description' => ['nullable', 'string'],
        ]);

        Program::create($data + ['is_active' => true]);

        return back()->with('flash', ['success' => 'Programme created.']);
    }

    public function update(Request $request, Program $programme): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:16', 'unique:programs,code,'.$programme->id],
            'name' => ['required', 'string', 'max:150'],
            'department_id' => ['required', 'exists:departments,id'],
            'type' => ['required', 'in:UG,PG'],
            'duration_years' => ['required', 'integer', 'min:1', 'max:6'],
            'total_credits' => ['required', 'integer', 'min:1', 'max:300'],
            'intake_capacity' => ['required', 'integer', 'min:1', 'max:2000'],
            'application_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'admission_fee' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $programme->update($data);

        return back()->with('flash', ['success' => 'Programme updated.']);
    }

    public function syncApplicationFees(Request $request, Program $programme): RedirectResponse
    {
        $data = $request->validate([
            'overrides' => ['present', 'array'],
            'overrides.*.reservation_category_id' => ['required', 'exists:reservation_categories,id'],
            'overrides.*.application_fee' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $seen = [];
        foreach ($data['overrides'] as $row) {
            $programme->applicationFeeOverrides()->updateOrCreate(
                ['reservation_category_id' => $row['reservation_category_id']],
                ['application_fee' => $row['application_fee']],
            );
            $seen[] = $row['reservation_category_id'];
        }

        // Remove rows the admin removed from the form.
        $programme->applicationFeeOverrides()
            ->whereNotIn('reservation_category_id', $seen ?: [0])
            ->delete();

        return back()->with('flash', ['success' => 'Application fee overrides saved.']);
    }

    public function syncAdmissionFees(Request $request, Program $programme): RedirectResponse
    {
        $data = $request->validate([
            'overrides' => ['present', 'array'],
            'overrides.*.reservation_category_id' => ['required', 'exists:reservation_categories,id'],
            'overrides.*.admission_fee' => ['required', 'numeric', 'min:0', 'max:10000000'],
        ]);

        $seen = [];
        foreach ($data['overrides'] as $row) {
            $programme->admissionFeeOverrides()->updateOrCreate(
                ['reservation_category_id' => $row['reservation_category_id']],
                ['admission_fee' => $row['admission_fee']],
            );
            $seen[] = $row['reservation_category_id'];
        }

        $programme->admissionFeeOverrides()
            ->whereNotIn('reservation_category_id', $seen ?: [0])
            ->delete();

        return back()->with('flash', ['success' => 'Admission fee overrides saved.']);
    }

    public function destroy(Program $programme): RedirectResponse
    {
        $programme->delete();

        return back()->with('flash', ['success' => 'Programme removed.']);
    }

    public function syncReservations(Request $request, Program $programme, SyncReservationMatrixAction $action): RedirectResponse
    {
        $data = $request->validate([
            'academic_session_id' => ['required', 'exists:academic_sessions,id'],
            'rows' => ['required', 'array'],
            'rows.*.category_id' => ['required', 'exists:reservation_categories,id'],
            'rows.*.seats' => ['required', 'integer', 'min:0'],
            'rows.*.relaxation_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $session = AcademicSession::findOrFail($data['academic_session_id']);
        $action->execute($session, $programme, $data['rows']);

        return back()->with('flash', ['success' => 'Reservation matrix saved.']);
    }
}
