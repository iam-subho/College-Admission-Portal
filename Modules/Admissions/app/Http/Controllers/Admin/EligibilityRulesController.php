<?php

namespace Modules\Admissions\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academics\Models\AcademicSubject;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\EligibilityRule;
use Modules\Admissions\Services\EligibilityEngine;

class EligibilityRulesController extends Controller
{
    public function index(): Response
    {
        $subjectOptions = AcademicSubject::query()
            ->active()
            ->orderBy('level')->orderBy('stream')->orderBy('ordering')->orderBy('name')
            ->get(['code', 'name', 'level', 'stream'])
            ->groupBy('level')
            ->map(fn ($rows) => $rows->map(fn ($s) => [
                'value' => $s->name,
                'label' => $s->name,
                'group' => $s->stream ?? 'Other',
                'sub' => $s->code,
            ])->values()->all());

        // Application counts per programme so admin sees how many will be re-evaluated.
        $appCounts = Application::query()
            ->whereIn('status', [Application::STATUS_SUBMITTED, Application::STATUS_VERIFIED, Application::STATUS_REJECTED])
            ->selectRaw('program_id, COUNT(*) as total, '.
                'SUM(CASE WHEN eligibility_verdict IN (?, ?) THEN 1 ELSE 0 END) as overridden',
                [Application::VERDICT_OVERRIDE_PASS, Application::VERDICT_OVERRIDE_FAIL])
            ->groupBy('program_id')
            ->get()
            ->keyBy('program_id');

        $programmes = Program::with(['department:id,code,name'])
            ->withCount(['reservations'])
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'department_id', 'intake_capacity', 'type'])
            ->map(function ($p) use ($appCounts) {
                $row = $appCounts->get($p->id);
                $p->app_total = $row?->total ?? 0;
                $p->app_overridden = $row?->overridden ?? 0;
                $p->app_re_evaluable = ($row?->total ?? 0) - ($row?->overridden ?? 0);

                return $p;
            });

        return Inertia::render('Admin/Eligibility', [
            'programmes' => $programmes,
            'rules' => EligibilityRule::with('program:id,code,name')->get(),
            'rule_types' => EligibilityRule::TYPES,
            'subject_options' => $subjectOptions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'rule_type' => ['required', Rule::in(EligibilityRule::TYPES)],
            'params' => ['required', 'array'],
            'label' => ['nullable', 'string', 'max:200'],
        ]);

        EligibilityRule::create($data + ['is_active' => true]);

        return back()->with('flash', ['success' => 'Eligibility rule added.']);
    }

    public function update(Request $request, EligibilityRule $rule): RedirectResponse
    {
        $data = $request->validate([
            'params' => ['required', 'array'],
            'label' => ['nullable', 'string', 'max:200'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $rule->update($data);

        return back()->with('flash', ['success' => 'Rule updated.']);
    }

    public function destroy(EligibilityRule $rule): RedirectResponse
    {
        $rule->delete();

        return back()->with('flash', ['success' => 'Rule removed.']);
    }

    /**
     * Re-run the eligibility engine on every non-draft application for a
     * programme. Skips applications where admin has manually overridden
     * (override_pass / override_fail), so admin decisions stay sticky.
     */
    public function reEvaluate(Program $programme, EligibilityEngine $engine): RedirectResponse
    {
        $applications = Application::query()
            ->where('program_id', $programme->id)
            ->whereIn('status', [Application::STATUS_SUBMITTED, Application::STATUS_VERIFIED, Application::STATUS_REJECTED])
            ->whereNotIn('eligibility_verdict', [Application::VERDICT_OVERRIDE_PASS, Application::VERDICT_OVERRIDE_FAIL])
            ->with(['student.academicRecords', 'program', 'session'])
            ->get();

        $skippedOverrides = Application::query()
            ->where('program_id', $programme->id)
            ->whereIn('status', [Application::STATUS_SUBMITTED, Application::STATUS_VERIFIED, Application::STATUS_REJECTED])
            ->whereIn('eligibility_verdict', [Application::VERDICT_OVERRIDE_PASS, Application::VERDICT_OVERRIDE_FAIL])
            ->count();

        $changed = 0;
        foreach ($applications as $app) {
            $verdict = $engine->run($app);

            $isChanged = $app->eligibility_verdict !== $verdict['verdict']
                || $app->eligibility_reasons !== $verdict['reasons'];

            $app->forceFill([
                'eligibility_verdict' => $verdict['verdict'],
                'eligibility_reasons' => $verdict['reasons'],
            ])->save();

            if ($isChanged) {
                $changed++;
            }
        }

        $total = $applications->count();
        $msg = "Re-evaluated {$total} application(s) for {$programme->code} — {$changed} verdict(s) changed";
        if ($skippedOverrides > 0) {
            $msg .= ". Skipped {$skippedOverrides} admin-overridden application(s)";
        }
        $msg .= '.';

        return back()->with('flash', ['success' => $msg]);
    }
}
