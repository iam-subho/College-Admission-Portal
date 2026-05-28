<?php

namespace Modules\Merit\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Models\Application;
use Modules\Merit\Models\MeritList;
use Modules\Merit\Models\MeritListEntry;

class MeritResultController extends Controller
{
    public function show(Request $request, Application $application): Response|RedirectResponse
    {
        $this->authorizeOwnership($application, $request);

        $latestList = MeritList::query()
            ->whereHas('round', fn ($q) => $q
                ->where('program_id', $application->program_id)
                ->where('academic_session_id', $application->academic_session_id)
            )
            ->where('status', MeritList::STATUS_PUBLISHED)
            ->with('round.program:id,code,name', 'round.session:id,code', 'cutoffs.category:id,code,name')
            ->latest('published_at')
            ->first();

        $entry = $latestList
            ? MeritListEntry::where('merit_list_id', $latestList->id)
                ->where('application_id', $application->id)
                ->with('category:id,code,name')
                ->first()
            : null;

        return Inertia::render('Student/MeritResult', [
            'application' => $application->load(['program:id,code,name,type', 'session:id,code']),
            'merit_list' => $latestList,
            'entry' => $entry,
            'cutoffs' => $latestList?->cutoffs->map(fn ($c) => [
                'category' => $c->category?->code,
                'category_name' => $c->category?->name,
                'seats_available' => $c->seats_available,
                'cutoff_score' => $c->cutoff_score !== null ? (float) $c->cutoff_score : null,
                'last_rank' => $c->last_rank,
            ]) ?? [],
        ]);
    }

    protected function authorizeOwnership(Application $application, Request $request): void
    {
        abort_unless(
            $application->student?->user_id === $request->user()->id,
            403,
            'Not your application.',
        );
    }
}
