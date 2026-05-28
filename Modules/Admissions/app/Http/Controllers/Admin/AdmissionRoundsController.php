<?php

namespace Modules\Admissions\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\AdmissionRound;

class AdmissionRoundsController extends Controller
{
    public function index(): Response
    {
        $rounds = AdmissionRound::query()
            ->with(['program:id,code,name,type', 'session:id,code', 'meritList:id,admission_round_id,status,total_candidates,published_at'])
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Admin/AdmissionRounds', [
            'rounds' => $rounds,
            'programs' => Program::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'type']),
            'sessions' => AcademicSession::orderByDesc('is_active')->get(['id', 'code', 'name', 'is_active']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'academic_session_id' => ['required', 'exists:academic_sessions,id'],
            'program_id' => ['required', 'exists:programs,id'],
            'round_number' => ['required', 'integer', 'min:1', 'max:10'],
            'name' => ['required', 'string', 'max:80'],
        ]);

        AdmissionRound::firstOrCreate(
            [
                'academic_session_id' => $data['academic_session_id'],
                'program_id' => $data['program_id'],
                'round_number' => $data['round_number'],
            ],
            [
                'name' => $data['name'],
                'status' => AdmissionRound::STATUS_PLANNING,
            ],
        );

        return back()->with('flash', ['success' => 'Admission round created.']);
    }

    public function updateStatus(Request $request, AdmissionRound $round): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:planning,open,closed,locked'],
        ]);

        $round->update(['status' => $data['status']]);

        return back()->with('flash', ['success' => 'Round status updated.']);
    }

    public function destroy(AdmissionRound $round): RedirectResponse
    {
        if ($round->meritList && $round->meritList->isPublished()) {
            return back()->with('flash', ['error' => 'Cannot delete a round whose merit list is published.']);
        }

        $round->delete();

        return back()->with('flash', ['success' => 'Round deleted.']);
    }
}
