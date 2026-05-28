<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Documents\Actions\UploadDocumentAction;
use Modules\Documents\Database\Seeders\DocumentTypeSeeder;
use Modules\Documents\Models\DocumentType;
use Modules\Documents\Models\UploadedDocument;
use Modules\Students\Models\Student;

beforeEach(function () {
    $this->seed(DocumentTypeSeeder::class);
    Storage::fake('documents_local');
    Storage::fake('documents_s3');
});

it('stores the active disk on each uploaded document', function () {
    config()->set('docs.default_disk', 'documents_local');

    $student = Student::factory()->create();
    $type = DocumentType::where('code', 'AADHAAR')->first();
    $file = UploadedFile::fake()->create('aadhaar.pdf', 100, 'application/pdf');

    $doc = app(UploadDocumentAction::class)->execute($student, $type, $file);

    expect($doc->disk)->toBe('documents_local');
    Storage::disk('documents_local')->assertExists($doc->path);
});

it('switching default disk leaves existing rows untouched', function () {
    $student = Student::factory()->create();
    $type = DocumentType::where('code', 'AADHAAR')->first();

    config()->set('docs.default_disk', 'documents_local');
    $localDoc = app(UploadDocumentAction::class)->execute(
        $student, $type, UploadedFile::fake()->create('first.pdf', 50, 'application/pdf'),
    );

    // Switch default
    config()->set('docs.default_disk', 'documents_s3');

    // The original row still lives on documents_local
    expect($localDoc->fresh()->disk)->toBe('documents_local');
    Storage::disk('documents_local')->assertExists($localDoc->path);
    Storage::disk('documents_s3')->assertMissing($localDoc->path);
});

it('new uploads go to the currently-default disk', function () {
    config()->set('docs.default_disk', 'documents_s3');

    $student = Student::factory()->create();
    $type = DocumentType::where('code', 'AADHAAR')->first();

    $doc = app(UploadDocumentAction::class)->execute(
        $student, $type, UploadedFile::fake()->create('via_s3.pdf', 50, 'application/pdf'),
    );

    expect($doc->disk)->toBe('documents_s3');
    Storage::disk('documents_s3')->assertExists($doc->path);
});

it('reads from the per-document disk, not the default', function () {
    $student = Student::factory()->create();
    $type = DocumentType::where('code', 'AADHAAR')->first();

    config()->set('docs.default_disk', 'documents_local');
    $doc = app(UploadDocumentAction::class)->execute(
        $student, $type, UploadedFile::fake()->create('x.pdf', 50, 'application/pdf'),
    );

    config()->set('docs.default_disk', 'documents_s3');

    expect($doc->storage()->exists($doc->path))->toBeTrue();
});
