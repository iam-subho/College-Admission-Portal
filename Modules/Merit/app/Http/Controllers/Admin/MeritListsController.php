<?php

namespace Modules\Merit\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Models\AdmissionRound;
use Modules\Admissions\Models\ReservationCategory;
use Modules\Merit\Models\MeritList;
use Modules\Merit\Services\MeritGenerator;

class MeritListsController extends Controller
{
    public function index(): Response
    {
        $rounds = AdmissionRound::query()
            ->with([
                'program:id,code,name,type',
                'session:id,code',
                'meritList:id,admission_round_id,status,total_candidates,max_score,generated_at,published_at',
            ])
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Admin/MeritLists', [
            'rounds' => $rounds,
        ]);
    }

    public function generate(Request $request, AdmissionRound $round, MeritGenerator $generator): RedirectResponse
    {
        $list = $generator->generate($round, $request->user()->id);

        return redirect()->route('admin.merit-lists.show', $list)->with('flash', [
            'success' => "Generated merit list with {$list->total_candidates} candidate(s).",
        ]);
    }

    public function show(MeritList $meritList): Response
    {
        $meritList->load([
            'round.program:id,code,name,type',
            'round.session:id,code',
            'cutoffs.category:id,code,name',
            'generator:id,name',
            'publisher:id,name',
        ]);

        $entries = $meritList->entries()
            ->with([
                'application:id,application_number,student_id',
                'application.student.user:id,name',
                'category:id,code,name',
            ])
            ->orderBy('overall_rank')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'overall_rank' => $e->overall_rank,
                'category_rank' => $e->category_rank,
                'application_number' => $e->application?->application_number,
                'applicant_name' => $e->application?->student?->user?->name,
                'category_code' => $e->category?->code,
                'total_score' => (float) $e->total_score,
                'test_score' => $e->test_score !== null ? (float) $e->test_score : null,
                'marks_pct' => (float) $e->marks_pct,
                'is_qualifying' => $e->is_qualifying,
                'is_absent' => $e->is_absent,
            ]);

        return Inertia::render('Admin/MeritList', [
            'merit_list' => $meritList,
            'entries' => $entries,
            'categories' => ReservationCategory::orderBy('ordering')->get(['id', 'code', 'name']),
        ]);
    }

    public function publish(Request $request, MeritList $meritList, MeritGenerator $generator): RedirectResponse
    {
        $generator->publish($meritList, $request->user()->id);

        return back()->with('flash', ['success' => 'Merit list published. Students can now view their result.']);
    }

    public function destroy(MeritList $meritList): RedirectResponse
    {
        if ($meritList->isPublished()) {
            return back()->with('flash', ['error' => 'Cannot delete a published merit list. Archive instead.']);
        }

        $meritList->delete();

        return redirect()->route('admin.merit-lists.index')->with('flash', ['success' => 'Draft merit list deleted.']);
    }
}
