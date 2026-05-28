<?php

namespace Modules\Tests\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Admissions\Models\Application;
use Modules\Tests\Models\AdmissionTestCandidate;
use Modules\Tests\Pdf\AdmitCardPdf;
use Modules\Tests\Services\TestCandidateRegistrar;

class AdmitCardController extends Controller
{
    public function show(Request $request, Application $application, TestCandidateRegistrar $registrar): InertiaResponse|RedirectResponse
    {
        $this->authorizeOwnership($application, $request);

        $registrar->ensureCandidateFor($application);

        $candidate = AdmissionTestCandidate::query()
            ->where('application_id', $application->id)
            ->with([
                'schedule.config:id,program_id,academic_session_id,is_test_enabled,max_marks,qualifying_marks,instructions,syllabus_url',
                'schedule.config.program:id,code,name',
                'schedule.config.session:id,code,name',
                'application:id,application_number',
            ])
            ->first();

        return Inertia::render('Student/AdmitCard', [
            'application' => $application->load([
                'program:id,code,name,type',
                'session:id,code,name',
            ]),
            'candidate' => $candidate,
            'config' => $candidate?->schedule?->config,
            'schedule' => $candidate?->schedule,
            'can_download' => $candidate
                && $candidate->admit_card_published
                && in_array($application->payment_status, [
                    Application::PAYMENT_PAID,
                    Application::PAYMENT_COVERED,
                    Application::PAYMENT_NOT_REQUIRED,
                ], true),
        ]);
    }

    public function download(Request $request, Application $application, AdmitCardPdf $pdf): Response|RedirectResponse
    {
        $this->authorizeOwnership($application, $request);

        $candidate = AdmissionTestCandidate::where('application_id', $application->id)->first();
        if (! $candidate || ! $candidate->admit_card_published) {
            return redirect()->route('student.admit-card.show', $application)
                ->with('flash', ['error' => 'Admit card is not yet published for your application.']);
        }

        if (! in_array($application->payment_status, [
            Application::PAYMENT_PAID,
            Application::PAYMENT_COVERED,
            Application::PAYMENT_NOT_REQUIRED,
        ], true)) {
            return redirect()->route('student.admit-card.show', $application)
                ->with('flash', ['error' => 'Pay the application fee first to download the admit card.']);
        }

        return $pdf($candidate);
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
