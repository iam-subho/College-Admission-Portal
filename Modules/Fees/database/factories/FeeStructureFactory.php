<?php

namespace Modules\Fees\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Fees\Models\FeeStructure;

class FeeStructureFactory extends Factory
{
    protected $model = FeeStructure::class;

    public function definition(): array
    {
        return [
            'academic_session_id' => AcademicSession::factory(),
            'program_id' => Program::factory(),
            'reservation_category_id' => null,
            'name' => 'General Fee Structure',
            'total_amount' => 0,
            'status' => 'draft',
        ];
    }
}
