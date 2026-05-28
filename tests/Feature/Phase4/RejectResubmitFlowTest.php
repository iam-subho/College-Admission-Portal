<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Documents\Actions\RejectDocumentAction;
use Modules\Documents\Actions\UploadDocumentAction;
use Modules\Documents\Database\Seeders\DocumentTypeSeeder;
use Modules\Documents\Models\DocumentType;
use Modules\Documents\Models\UploadedDocument;
use Modules\Students\Models\Student;
use Modules\Users\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(DocumentTypeSeeder::class);
    Storage::fake('documents_local');
    config()->set('docs.default_disk', 'documents_local');
});

function makeAdminForDocs(): User
{
    $u = User::create([
        'name' => 'Admin',
        'email' => 'admin-doc@svnc.test',
        'mobile' => '9988991199',
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $u->assignRole('admin');

    return $u;
}

it('records rejection reason and resets status on resubmit', function () {
    $admin = makeAdminForDocs();
    $student = Student::factory()->create();
    $type = DocumentType::where('code', 'AADHAAR')->first();

    $doc = app(UploadDocumentAction::class)->execute(
        $student, $type, UploadedFile::fake()->create('first.pdf', 50, 'application/pdf'),
    );

    app(RejectDocumentAction::class)->execute($doc, $admin, 'Photo unclear, please re-scan.');

    $doc->refresh();
    expect($doc->status)->toBe('rejected');
    expect($doc->rejection_reason)->toContain('unclear');

    // Student re-uploads — same student + type + application → updates existing row
    $resubmitted = app(UploadDocumentAction::class)->execute(
        $student, $type, UploadedFile::fake()->create('second.pdf', 50, 'application/pdf'),
    );

    expect($resubmitted->id)->toBe($doc->id);
    expect($resubmitted->status)->toBe('pending');
    expect($resubmitted->rejection_reason)->toBeNull();
});

it('the verification record is preserved after resubmit', function () {
    $admin = makeAdminForDocs();
    $student = Student::factory()->create();
    $type = DocumentType::where('code', 'AADHAAR')->first();

    $doc = app(UploadDocumentAction::class)->execute(
        $student, $type, UploadedFile::fake()->create('a.pdf', 50, 'application/pdf'),
    );

    app(RejectDocumentAction::class)->execute($doc, $admin, 'Re-scan required');

    expect($doc->verifications()->count())->toBe(1);

    app(UploadDocumentAction::class)->execute(
        $student, $type, UploadedFile::fake()->create('b.pdf', 50, 'application/pdf'),
    );

    // Verification history is preserved
    expect($doc->fresh()->verifications()->count())->toBe(1);
});

it('http reject endpoint requires a reason and writes the row', function () {
    $admin = makeAdminForDocs();
    $student = Student::factory()->create();
    $type = DocumentType::where('code', 'AADHAAR')->first();

    $doc = app(UploadDocumentAction::class)->execute(
        $student, $type, UploadedFile::fake()->create('a.pdf', 50, 'application/pdf'),
    );

    $this->actingAs($admin)
        ->post("/admin/documents/{$doc->id}/reject", ['reason' => ''])
        ->assertSessionHasErrors('reason');

    $this->actingAs($admin)
        ->post("/admin/documents/{$doc->id}/reject", ['reason' => 'Caste cert is illegible'])
        ->assertRedirect();

    expect($doc->fresh()->status)->toBe('rejected');
});
