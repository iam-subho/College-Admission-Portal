<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

function adminUserBulk(): User
{
    $u = User::create([
        'name' => 'Admin Bulk',
        'email' => 'admin-bulk@svnc.test',
        'mobile' => '9988002222',
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $u->assignRole('admin');

    return $u;
}

it('bulk approves multiple documents in one request', function () {
    $admin = adminUserBulk();
    $type = DocumentType::where('code', 'AADHAAR')->first();

    $docs = collect();
    for ($i = 0; $i < 4; $i++) {
        $docs->push(app(UploadDocumentAction::class)->execute(
            Student::factory()->create(),
            $type,
            UploadedFile::fake()->create("a_{$i}.pdf", 50, 'application/pdf'),
        ));
    }

    $this->actingAs($admin)
        ->post('/admin/documents/bulk-approve', [
            'document_ids' => $docs->pluck('id')->all(),
        ])
        ->assertRedirect();

    foreach ($docs as $d) {
        expect($d->fresh()->status)->toBe('approved');
    }

    expect(UploadedDocument::whereIn('id', $docs->pluck('id'))->where('status', 'approved')->count())->toBe(4);
});

it('non-admin cannot use bulk approve', function () {
    $student = User::create([
        'name' => 'Stu',
        'email' => 'stu-bulk@svnc.test',
        'mobile' => '9988002233',
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $student->assignRole('student');

    $this->actingAs($student)
        ->post('/admin/documents/bulk-approve', ['document_ids' => [1, 2]])
        ->assertForbidden();
});

it('rejects empty selection', function () {
    $admin = adminUserBulk();

    $this->actingAs($admin)
        ->post('/admin/documents/bulk-approve', ['document_ids' => []])
        ->assertSessionHasErrors('document_ids');
});
