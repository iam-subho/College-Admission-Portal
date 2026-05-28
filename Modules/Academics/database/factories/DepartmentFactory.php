<?php

namespace Modules\Academics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academics\Models\Department;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('DEPT_???'),
            'name' => 'School of '.$this->faker->word(),
            'head_of_dept' => $this->faker->name(),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
