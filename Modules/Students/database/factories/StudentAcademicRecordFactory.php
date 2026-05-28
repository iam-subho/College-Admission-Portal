<?php

namespace Modules\Students\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Students\Models\Student;
use Modules\Students\Models\StudentAcademicRecord;

class StudentAcademicRecordFactory extends Factory
{
    protected $model = StudentAcademicRecord::class;

    public function definition(): array
    {
        $subjects = collect(['Physics', 'Chemistry', 'Mathematics', 'English', 'Computer Science'])
            ->map(fn ($name) => [
                'name' => $name,
                'full_marks' => 100,
                'obtained_marks' => $this->faker->numberBetween(60, 95),
            ])
            ->all();

        $totalFm = collect($subjects)->sum('full_marks');
        $totalOm = collect($subjects)->sum('obtained_marks');

        return [
            'student_id' => Student::factory(),
            'level' => StudentAcademicRecord::LEVEL_12TH,
            'board' => 'CBSE',
            'passing_year' => 2026,
            'percentage' => round(($totalOm / $totalFm) * 100, 2),
            'full_marks' => $totalFm,
            'obtained_marks' => $totalOm,
            'school_name' => $this->faker->company().' School',
            'stream' => 'Science',
            'subjects' => $subjects,
        ];
    }

    public function tenth(): self
    {
        return $this->state([
            'level' => StudentAcademicRecord::LEVEL_10TH,
            'stream' => null,
            'subjects' => null,
            'full_marks' => 500,
            'obtained_marks' => 425,
            'percentage' => 85.00,
        ]);
    }
}
