<?php

namespace Modules\Admissions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Admissions\Models\AcademicSession;

class AcademicSessionFactory extends Factory
{
    protected $model = AcademicSession::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 year', '+1 year');

        return [
            'code' => $this->faker->unique()->numerify('20##-2#'),
            'name' => 'Academic Session '.$start->format('Y'),
            'commencement_date' => $start,
            'application_open_date' => (clone $start)->modify('-90 days'),
            'application_close_date' => (clone $start)->modify('-30 days'),
            'is_active' => false,
            'status' => AcademicSession::STATUS_PLANNING,
        ];
    }

    public function active(): self
    {
        return $this->state(['is_active' => true, 'status' => AcademicSession::STATUS_ACTIVE]);
    }
}
