<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Documents\Actions\FetchFromDigilockerAction;
use Modules\Documents\Database\Seeders\DocumentTypeSeeder;
use Modules\Documents\Models\DigilockerLink;
use Modules\Documents\Models\DocumentType;
use Modules\Documents\Services\Digilocker\DigilockerException;
use Modules\Students\Models\Student;
use Modules\Users\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(DocumentTypeSeeder::class);
    Storage::fake('documents_local');
    config()->set('docs.default_disk', 'documents_local');
    config()->set('digilocker.default', 'stub');
});

function makeStudentUserWithLink(): array
{
    $user = User::create([
        'name' => 'DL Student',
        'email' => 'dl@svnc.test',
        'mobile' => '9988550000',
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $user->assignRole('student');

    $student = Student::create(['user_id' => $user->id]);

    $link = DigilockerLink::create(['user_id' => $user->id]);
    $link->digilocker_user_id = 'STUB_USER_1';
    $link->access_token = 'stub-access-token';
    $link->refresh_token = 'stub-refresh-token';
    $link->linked_at = now();
    $link->save();

    return [$user, $student];
}

it('fetches a document from the stub DigiLocker driver and saves it', function () {
    [$user, $student] = makeStudentUserWithLink();
    $type = DocumentType::where('code', 'AADHAAR')->first();

    $doc = app(FetchFromDigilockerAction::class)->execute($student, $user, $type);

    expect($doc->source)->toBe('digilocker');
    expect($doc->digilocker_issued_by)->toContain('Unique Identification');
    expect($doc->digilocker_uri)->toContain('stub');
    expect($doc->mime)->toBe('application/pdf');
    Storage::disk('documents_local')->assertExists($doc->path);
});

it('fails cleanly when user has no DigiLocker link', function () {
    $user = User::create([
        'name' => 'Unlinked',
        'email' => 'unlinked@svnc.test',
        'mobile' => '9988440000',
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $user->assignRole('student');
    $student = Student::create(['user_id' => $user->id]);
    $type = DocumentType::where('code', 'AADHAAR')->first();

    expect(fn () => app(FetchFromDigilockerAction::class)->execute($student, $user, $type))
        ->toThrow(DigilockerException::class);
});

it('fails cleanly for a document type with no digilocker mapping', function () {
    [$user, $student] = makeStudentUserWithLink();
    $type = DocumentType::where('code', 'PHOTO')->first(); // no digilocker_doc_type

    expect(fn () => app(FetchFromDigilockerAction::class)->execute($student, $user, $type))
        ->toThrow(DigilockerException::class);
});

it('encrypts DigiLocker tokens at rest', function () {
    [$user] = makeStudentUserWithLink();

    $link = DigilockerLink::where('user_id', $user->id)->first();
    expect($link->access_token_enc)->not->toBe('stub-access-token');
    expect($link->access_token)->toBe('stub-access-token');
});

it('http endpoint falls back to manual upload UI when fetch fails', function () {
    $user = User::create([
        'name' => 'Unlinked HTTP',
        'email' => 'unlinked-http@svnc.test',
        'mobile' => '9988440101',
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $user->assignRole('student');
    Student::create(['user_id' => $user->id]);

    $type = DocumentType::where('code', 'AADHAAR')->first();

    $this->actingAs($user)
        ->post("/student/uploads/{$type->id}/digilocker")
        ->assertRedirect();
});
