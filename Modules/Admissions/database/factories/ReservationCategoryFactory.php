<?php

namespace Modules\Admissions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Admissions\Models\ReservationCategory;

class ReservationCategoryFactory extends Factory
{
    protected $model = ReservationCategory::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('CAT_???'),
            'name' => $this->faker->words(2, true),
            'is_horizontal' => false,
            'default_percentage' => $this->faker->randomFloat(2, 5, 30),
            'is_active' => true,
            'ordering' => 0,
        ];
    }
}
