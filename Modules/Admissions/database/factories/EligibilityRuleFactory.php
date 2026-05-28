<?php

namespace Modules\Admissions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\EligibilityRule;

class EligibilityRuleFactory extends Factory
{
    protected $model = EligibilityRule::class;

    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'rule_type' => EligibilityRule::TYPE_MIN_PERCENTAGE,
            'params' => ['level' => '12th', 'value' => 60],
            'label' => 'Minimum 60% in 12th',
            'is_active' => true,
        ];
    }
}
