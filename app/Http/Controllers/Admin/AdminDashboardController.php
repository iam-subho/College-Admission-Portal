<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academics\Models\Department;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Fees\Models\FeeHead;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'sessions' => AcademicSession::count(),
                'active_session' => AcademicSession::where('is_active', true)->first(),
                'departments' => Department::count(),
                'programmes' => Program::count(),
                'fee_heads' => FeeHead::count(),
            ],
        ]);
    }
}
