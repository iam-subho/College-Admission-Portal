<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Students\Models\Student;

class StudentDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $student = Student::firstOrCreate(['user_id' => $request->user()->id]);
        $student->load(['academicRecords', 'applications.program:id,code,name,type']);

        return Inertia::render('Student/Dashboard', [
            'student' => $student,
            'profile_completion' => $student->profile_completion,
            'applications' => $student->applications,
        ]);
    }
}
