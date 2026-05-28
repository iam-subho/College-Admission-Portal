<?php

namespace Modules\Academics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academics\Models\Department;
use Modules\Academics\Models\Program;

class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('UG####'),
            'name' => 'B.Sc. (Hons.) '.$this->faker->word(),
            'department_id' => Department::factory(),
            'type' => Program::TYPE_UG,
            'duration_years' => 3,
            'total_credits' => 120,
            'intake_capacity' => 60,
            'is_active' => true,
        ];
    }

    public function pg(): self
    {
        return $this->state([
            'code' => $this->faker->unique()->bothify('PG####'),
            'name' => 'M.A. '.$this->faker->word(),
            'type' => Program::TYPE_PG,
            'duration_years' => 2,
            'total_credits' => 80,
        ]);
    }
}
