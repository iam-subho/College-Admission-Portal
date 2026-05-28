<?php

namespace Modules\Students\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Students\Models\Student;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'dob' => $this->faker->dateTimeBetween('-25 years', '-17 years')->format('Y-m-d'),
            'nationality' => 'Indian',
            'religion' => $this->faker->randomElement(['Hindu', 'Muslim', 'Christian', 'Sikh']),
            'father_name' => $this->faker->name('male'),
            'father_occupation' => 'Business',
            'mother_name' => $this->faker->name('female'),
            'mother_occupation' => 'Homemaker',
            'annual_family_income' => $this->faker->numberBetween(200000, 1500000),
            'address' => $this->faker->address(),
            'state' => 'Gujarat',
            'district' => 'Anand',
            'pincode' => '388120',
            'domicile_state' => 'Gujarat',
        ];
    }
}
