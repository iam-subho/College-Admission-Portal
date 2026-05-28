<?php

namespace Modules\Documents\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Documents\Models\DocumentType;
use Modules\Documents\Models\UploadedDocument;
use Modules\Students\Models\Student;

class UploadedDocumentFactory extends Factory
{
    protected $model = UploadedDocument::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'document_type_id' => DocumentType::factory(),
            'disk' => 'documents_local',
            'path' => 'students/'.$this->faker->uuid().'.pdf',
            'original_name' => $this->faker->word().'.pdf',
            'mime' => 'application/pdf',
            'size_bytes' => $this->faker->numberBetween(50_000, 500_000),
            'checksum_sha256' => hash('sha256', $this->faker->text(200)),
            'source' => UploadedDocument::SOURCE_MANUAL,
            'status' => UploadedDocument::STATUS_PENDING,
        ];
    }
}
