<?php

namespace Modules\Admissions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Students\Models\Student;

class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'program_id' => Program::factory(),
            'academic_session_id' => AcademicSession::factory()->active(),
            'status' => Application::STATUS_DRAFT,
            'draft_data' => [],
        ];
    }
}
