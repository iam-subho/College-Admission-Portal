<?php

namespace Modules\Merit\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AdmissionRound;
use Modules\Merit\Models\MeritList;

/**
 * Anonymous public-facing merit list view. By design, NO names / emails / DOBs.
 * Students recognise themselves by their own application_number.
 */
class PublicMeritController extends Controller
{
    public function show(Request $request, string $programCode, int $roundNumber): Response
    {
        $program = Program::where('code', $programCode)->where('is_active', true)->firstOrFail();
        $round = AdmissionRound::where('program_id', $program->id)
            ->where('round_number', $roundNumber)
            ->firstOrFail();

        $list = MeritList::where('admission_round_id', $round->id)
            ->where('status', MeritList::STATUS_PUBLISHED)
            ->with(['round.session:id,code,name', 'cutoffs.category:id,code,name'])
            ->firstOrFail();

        $entries = $list->entries()
            ->with(['application:id,application_number', 'category:id,code'])
            ->orderBy('overall_rank')
            ->get(['id', 'merit_list_id', 'application_id', 'reservation_category_id', 'overall_rank', 'category_rank', 'total_score', 'is_qualifying', 'is_absent'])
            ->map(fn ($e) => [
                'overall_rank' => $e->overall_rank,
                'category_rank' => $e->category_rank,
                'application_number' => $e->application?->application_number,
                'category' => $e->category?->code,
                'score' => round((float) $e->total_score, 2),
                'is_qualifying' => $e->is_qualifying,
                'is_absent' => $e->is_absent,
            ]);

        return Inertia::render('Public/Merit', [
            'program' => $program->only(['id', 'code', 'name', 'type']),
            'round' => $round->only(['id', 'round_number', 'name']),
            'session' => $list->round->session->only(['code', 'name']),
            'merit_list' => [
                'id' => $list->id,
                'total_candidates' => $list->total_candidates,
                'published_at' => $list->published_at,
            ],
            'entries' => $entries,
            'cutoffs' => $list->cutoffs->map(fn ($c) => [
                'category' => $c->category?->code,
                'category_name' => $c->category?->name,
                'seats_available' => $c->seats_available,
                'cutoff_score' => $c->cutoff_score !== null ? (float) $c->cutoff_score : null,
                'last_rank' => $c->last_rank,
                'candidates_in_category' => $c->candidates_in_category,
            ]),
        ]);
    }
}
