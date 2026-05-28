<?php

namespace Modules\Admissions\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\Admissions\Models\Application;

/**
 * Renders the student application acknowledgement as an A4 PDF.
 * Mirrors the on-screen receipt at /student/applications/{id} but
 * server-generated so the student can email / print it cleanly.
 */
class ApplicationReceiptPdf
{
    public function __invoke(Application $application): Response
    {
        $application->load([
            'student.user:id,name,email,mobile',
            'student.category:id,code,name',
            'student.academicRecords',
            'program.department:id,code,name',
            'session:id,code,name',
            'courseSelections.pool',
            'paymentOrders' => fn ($q) => $q->orderByDesc('id'),
        ]);

        $student = $application->student;

        // Group course selections by NEP category for the subject-combination table.
        $picksByCategory = $application->courseSelections->groupBy('category')
            ->map(fn ($rows) => $rows->map(fn ($sel) => [
                'code' => $sel->pool?->course_code,
                'name' => $sel->pool?->course_name,
                'credits' => $sel->pool?->credits,
            ])->values()->all());

        $html = view('admissions::application_receipt', [
            'application' => $application,
            'student' => $student,
            'user' => $student?->user,
            'program' => $application->program,
            'session' => $application->session,
            'academic_records' => $student?->academicRecords ?? collect(),
            'picks' => $picksByCategory,
            'categories' => \Modules\Academics\Models\ProgrammeCoursePool::CATEGORIES,
            'paid_order' => $application->paymentOrders->where('status', 'paid')->first(),
        ])->render();

        $safeNumber = str_replace(['/', '\\'], '-', (string) ($application->application_number ?? 'DRAFT'));
        $filename = 'Application-'.$safeNumber.'.pdf';

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}
