<?php

namespace Modules\Fees\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Fees\Models\FeeHead;

class FeeHeadFactory extends Factory
{
    protected $model = FeeHead::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('FH_???'),
            'name' => $this->faker->words(2, true),
            'category' => FeeHead::CAT_TUITION,
            'is_refundable' => false,
            'ordering' => 0,
            'is_active' => true,
        ];
    }
}
