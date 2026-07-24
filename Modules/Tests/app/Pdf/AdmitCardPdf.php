<?php

namespace Modules\Tests\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\Admissions\Models\SiteSetting;
use Modules\Tests\Models\AdmissionTestCandidate;

/**
 * Generates the admit card PDF for a candidate. Marks the candidate as
 * downloaded on first call.
 */
class AdmitCardPdf
{
    public function __invoke(AdmissionTestCandidate $candidate): Response
    {
        $candidate->load([
            'application.student.user:id,name,email,mobile',
            'application.program:id,code,name,type',
            'application.session:id,code,name',
            'schedule.config.program:id,code,name',
            'schedule.config.session:id,code,name',
        ]);

        $html = view('tests::admit_card', [
            'candidate' => $candidate,
            'application' => $candidate->application,
            'student' => $candidate->application?->student,
            'user' => $candidate->application?->student?->user,
            'program' => $candidate->application?->program,
            'session' => $candidate->application?->session,
            'schedule' => $candidate->schedule,
            'config' => $candidate->schedule?->config,
            'site' => SiteSetting::resolved(),
        ])->render();

        if (! $candidate->admit_card_downloaded_at) {
            $candidate->forceFill(['admit_card_downloaded_at' => now()])->save();
        }

        // Roll numbers contain "/" (e.g. TEST/2026-27/UGCS01/000001) which is
        // illegal in HTTP Content-Disposition filenames. Replace with "-".
        $safeRoll = str_replace(['/', '\\'], '-', (string) $candidate->roll_number);
        $filename = 'AdmitCard-'.$safeRoll.'.pdf';

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}
