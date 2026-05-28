<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Modules\Documents\Actions\UploadDocumentAction;
use Modules\Documents\Database\Seeders\DocumentTypeSeeder;
use Modules\Documents\Models\DocumentType;
use Modules\Students\Models\Student;

beforeEach(function () {
    $this->seed(DocumentTypeSeeder::class);
    Storage::fake('documents_local');
    Storage::fake('documents_s3');
    config()->set('docs.default_disk', 'documents_local');
});

it('migrates documents from local to s3 with checksum verification', function () {
    $student = Student::factory()->create();
    $type = DocumentType::where('code', 'AADHAAR')->first();

    $docs = collect();
    for ($i = 0; $i < 3; $i++) {
        $docs->push(app(UploadDocumentAction::class)->execute(
            Student::factory()->create(),
            $type,
            UploadedFile::fake()->create("file_{$i}.pdf", 50, 'application/pdf'),
        ));
    }

    $exitCode = Artisan::call('docs:migrate-disk', [
        '--from' => 'documents_local',
        '--to' => 'documents_s3',
    ]);

    expect($exitCode)->toBe(0);

    foreach ($docs as $d) {
        expect($d->fresh()->disk)->toBe('documents_s3');
        Storage::disk('documents_s3')->assertExists($d->path);
        // Source not deleted by default
        Storage::disk('documents_local')->assertExists($d->path);
    }
});

it('dry-run does not modify the database', function () {
    $student = Student::factory()->create();
    $type = DocumentType::where('code', 'AADHAAR')->first();
    $doc = app(UploadDocumentAction::class)->execute(
        $student, $type, UploadedFile::fake()->create('a.pdf', 50, 'application/pdf'),
    );

    Artisan::call('docs:migrate-disk', [
        '--from' => 'documents_local',
        '--to' => 'documents_s3',
        '--dry-run' => true,
    ]);

    expect($doc->fresh()->disk)->toBe('documents_local');
    Storage::disk('documents_s3')->assertMissing($doc->path);
});

it('refuses unknown disks', function () {
    $exit = Artisan::call('docs:migrate-disk', [
        '--from' => 'bogus_disk',
        '--to' => 'documents_s3',
    ]);

    expect($exit)->not->toBe(0);
});

it('--delete-source removes the file from the source disk after verified copy', function () {
    $student = Student::factory()->create();
    $type = DocumentType::where('code', 'AADHAAR')->first();
    $doc = app(UploadDocumentAction::class)->execute(
        $student, $type, UploadedFile::fake()->create('zap.pdf', 50, 'application/pdf'),
    );

    Artisan::call('docs:migrate-disk', [
        '--from' => 'documents_local',
        '--to' => 'documents_s3',
        '--delete-source' => true,
    ]);

    Storage::disk('documents_s3')->assertExists($doc->fresh()->path);
    Storage::disk('documents_local')->assertMissing($doc->path);
});
