<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Documents\Actions\UploadDocumentAction;
use Modules\Documents\Database\Seeders\DocumentTypeSeeder;
use Modules\Documents\Models\DocumentType;
use Modules\Students\Models\Student;

beforeEach(function () {
    $this->seed(DocumentTypeSeeder::class);
    Storage::fake('documents_local');
    config()->set('docs.default_disk', 'documents_local');
});

it('rejects a file with disallowed mime type', function () {
    $student = Student::factory()->create();
    $type = DocumentType::where('code', 'PHOTO')->first(); // jpg/png only

    $file = UploadedFile::fake()->create('photo.exe', 100, 'application/x-msdownload');

    expect(fn () => app(UploadDocumentAction::class)->execute($student, $type, $file))
        ->toThrow(ValidationException::class);
});

it('rejects a file exceeding max_size_kb', function () {
    $student = Student::factory()->create();
    $type = DocumentType::where('code', 'PHOTO')->first(); // 300 KB cap

    // 500 KB file
    $file = UploadedFile::fake()->create('big.jpg', 500, 'image/jpeg');

    expect(fn () => app(UploadDocumentAction::class)->execute($student, $type, $file))
        ->toThrow(ValidationException::class);
});

it('accepts a valid file and stores checksum', function () {
    $student = Student::factory()->create();
    $type = DocumentType::where('code', 'AADHAAR')->first();

    $file = UploadedFile::fake()->create('aadhaar.pdf', 100, 'application/pdf');
    $doc = app(UploadDocumentAction::class)->execute($student, $type, $file);

    expect($doc->checksum_sha256)->not->toBeEmpty();
    expect(strlen($doc->checksum_sha256))->toBe(64);
    expect($doc->source)->toBe('manual');
    expect($doc->status)->toBe('pending');
});
