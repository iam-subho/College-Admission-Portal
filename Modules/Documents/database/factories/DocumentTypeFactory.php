<?php

namespace Modules\Documents\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Documents\Models\DocumentType;

class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('DOC_???')),
            'label' => $this->faker->words(2, true),
            'required_by_default' => true,
            'allowed_mimes' => ['application/pdf', 'image/jpeg'],
            'max_size_kb' => 2048,
            'is_active' => true,
        ];
    }
}
