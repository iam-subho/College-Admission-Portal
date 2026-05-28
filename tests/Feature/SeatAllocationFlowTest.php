<?php

use App\Models\User;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\AdmissionRound;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\ProgramReservation;
use Modules\Admissions\Models\ReservationCategory;
use Modules\Merit\Models\MeritList;
use Modules\Merit\Models\MeritListEntry;
use Modules\Seats\Models\SeatAcceptance;
use Modules\Seats\Models\SeatAllocation;
use Modules\Seats\Services\SeatAllocator;
use Modules\Students\Models\Student;
use Modules\Students\Models\StudentAcademicRecord;
use Modules\Users\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeAdmin8(): User
{
    $u = User::create([
        'name' => 'Admin', 'email' => fake()->unique()->safeEmail(),
        'mobile' => '99'.fake()->numerify('########'), 'password' => bcrypt('x'), 'status' => 'active',
    ]);
    $u->assignRole('admin');

    return $u;
}

function makeMeritReadyRound(int $seats = 2): array
{
    $session = AcademicSession::factory()->active()->create();
    $program = Program::factory()->create();

    $cat = ReservationCategory::firstOrCreate(['code' => 'UR'], [
        'name' => 'Unreserved', 'is_horizontal' => false, 'is_active' => true, 'ordering' => 1,
    ]);

    ProgramReservation::create([
        'academic_session_id' => $session->id,
        'program_id' => $program->id,
        'reservation_category_id' => $cat->id,
        'seats' => $seats,
        'relaxation_percent' => 0,
    ]);

    $round = AdmissionRound::create([
        'academic_session_id' => $session->id,
        'program_id' => $program->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'status' => AdmissionRound::STATUS_MERIT_PUBLISHED,
        'acceptance_window_days' => 7,
    ]);

    return compact('session', 'program', 'cat', 'round');
}

function makeMeritEntry(MeritList $list, Program $program, AcademicSession $session, ReservationCategory $cat, int $rank, float $score): Application
{
    $student = Student::factory()->create(['reservation_category_id' => $cat->id]);
    StudentAcademicRecord::factory()->create([
        'student_id' => $student->id,
        'level' => '12th',
        'percentage' => $score,
    ]);

    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_SUBMITTED,
        'payment_status' => Application::PAYMENT_PAID,
        'application_number' => 'SVNC/'.$program->code.'/'.fake()->unique()->numerify('######'),
    ]);

    MeritListEntry::create([
        'merit_list_id' => $list->id,
        'application_id' => $app->id,
        'reservation_category_id' => $cat->id,
        'overall_rank' => $rank,
        'category_rank' => $rank,
        'total_score' => $score,
        'marks_pct' => $score,
        'is_qualifying' => true,
        'is_absent' => false,
    ]);

    return $app;
}

it('generates allotments for top N qualifying candidates per category', function () {
    ['session' => $s, 'program' => $p, 'cat' => $c, 'round' => $r] = makeMeritReadyRound(seats: 2);
    $list = MeritList::create([
        'admission_round_id' => $r->id,
        'status' => MeritList::STATUS_PUBLISHED,
        'total_candidates' => 3,
        'formula_snapshot' => ['test_enabled' => false],
        'published_at' => now(),
    ]);

    $a1 = makeMeritEntry($list, $p, $s, $c, 1, 90.0);
    $a2 = makeMeritEntry($list, $p, $s, $c, 2, 85.0);
    $a3 = makeMeritEntry($list, $p, $s, $c, 3, 80.0);

    $result = app(SeatAllocator::class)->generate($r);

    expect($result['allotted'])->toBe(2);
    expect(SeatAllocation::count())->toBe(2);

    $allocated = SeatAllocation::pluck('application_id')->all();
    expect($allocated)->toContain($a1->id);
    expect($allocated)->toContain($a2->id);
    expect($allocated)->not->toContain($a3->id);
});

it('promotes next waitlisted candidate immediately when current declines', function () {
    ['session' => $s, 'program' => $p, 'cat' => $c, 'round' => $r] = makeMeritReadyRound(seats: 1);
    $list = MeritList::create([
        'admission_round_id' => $r->id,
        'status' => MeritList::STATUS_PUBLISHED,
        'total_candidates' => 2,
        'formula_snapshot' => ['test_enabled' => false],
        'published_at' => now(),
    ]);
    $a1 = makeMeritEntry($list, $p, $s, $c, 1, 90.0);
    $a2 = makeMeritEntry($list, $p, $s, $c, 2, 85.0);

    $allocator = app(SeatAllocator::class);
    $allocator->generate($r);

    expect(SeatAllocation::count())->toBe(1);
    $first = SeatAllocation::first();
    expect($first->application_id)->toBe($a1->id);
    expect($first->status)->toBe(SeatAllocation::STATUS_ALLOTTED);

    $allocator->recordAction($first, SeatAcceptance::ACTION_DECLINE, null, 'Test decline');

    expect($first->fresh()->status)->toBe(SeatAllocation::STATUS_DECLINED);
    expect(SeatAllocation::count())->toBe(2);

    $second = SeatAllocation::where('application_id', $a2->id)->first();
    expect($second)->not->toBeNull();
    expect($second->status)->toBe(SeatAllocation::STATUS_ALLOTTED);
    expect($second->audit_remark)->toContain('Promoted from waitlist');
});

it('expires stale allotments via the rollover command and promotes next', function () {
    ['session' => $s, 'program' => $p, 'cat' => $c, 'round' => $r] = makeMeritReadyRound(seats: 1);
    $list = MeritList::create([
        'admission_round_id' => $r->id,
        'status' => MeritList::STATUS_PUBLISHED,
        'total_candidates' => 2,
        'formula_snapshot' => ['test_enabled' => false],
        'published_at' => now(),
    ]);
    $a1 = makeMeritEntry($list, $p, $s, $c, 1, 90.0);
    $a2 = makeMeritEntry($list, $p, $s, $c, 2, 85.0);

    $allocator = app(SeatAllocator::class);
    $allocator->generate($r);

    $first = SeatAllocation::first();
    $first->forceFill(['expires_at' => now()->subDay()])->save();

    $this->artisan('seats:rollover-expired')->assertSuccessful();

    expect($first->fresh()->status)->toBe(SeatAllocation::STATUS_EXPIRED);
    expect(SeatAllocation::where('application_id', $a2->id)->where('status', 'allotted')->exists())->toBeTrue();
});

it('spot allots a walk-in applicant directly bypassing the merit list', function () {
    ['session' => $s, 'program' => $p, 'cat' => $c, 'round' => $r] = makeMeritReadyRound(seats: 5);
    $admin = makeAdmin8();

    $student = Student::factory()->create(['reservation_category_id' => $c->id]);
    $student->user->assignRole('student');
    $walkin = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $p->id,
        'academic_session_id' => $s->id,
        'status' => Application::STATUS_SUBMITTED,
        'payment_status' => Application::PAYMENT_PAID,
        'application_number' => 'SVNC/'.$p->code.'/SPOT',
    ]);

    $alloc = app(SeatAllocator::class)->spotAllot($r, $walkin, $c->id, $admin->id, 'Walk-in test');

    expect($alloc->source)->toBe(SeatAllocation::SOURCE_SPOT);
    expect($alloc->application_id)->toBe($walkin->id);
    expect($alloc->admitted_by_admin_id)->toBe($admin->id);

    expect(SeatAcceptance::where('seat_allocation_id', $alloc->id)
        ->where('action', SeatAcceptance::ACTION_SPOT_ALLOT)->exists())->toBeTrue();
});

it('marks allocation as admitted when admission_fee order is captured via webhook', function () {
    ['session' => $s, 'program' => $p, 'cat' => $c, 'round' => $r] = makeMeritReadyRound(seats: 1);
    $list = MeritList::create([
        'admission_round_id' => $r->id,
        'status' => MeritList::STATUS_PUBLISHED,
        'total_candidates' => 1,
        'formula_snapshot' => ['test_enabled' => false],
        'published_at' => now(),
    ]);
    $a1 = makeMeritEntry($list, $p, $s, $c, 1, 90.0);

    $allocator = app(SeatAllocator::class);
    $allocator->generate($r);
    $alloc = SeatAllocation::first();
    $allocator->recordAction($alloc, SeatAcceptance::ACTION_ACCEPT, $a1->student->user_id);
    expect($alloc->fresh()->status)->toBe(SeatAllocation::STATUS_ACCEPTED);

    $gateway = \Modules\Payments\Models\PaymentGateway::firstOrCreate(
        ['code' => 'razorpay'],
        ['display_name' => 'Razorpay', 'mode' => 'stub', 'is_active' => true, 'config_encrypted' => encrypt(['key_id' => 'x'])],
    );
    $order = \Modules\Payments\Models\PaymentOrder::create([
        'application_id' => $a1->id,
        'payment_gateway_id' => $gateway->id,
        'order_number' => 'ADM/0001',
        'purpose' => \Modules\Payments\Models\PaymentOrder::PURPOSE_ADMISSION_FEE,
        'amount' => 10000, 'convenience_fee' => 0, 'gst' => 0, 'total' => 10000,
        'currency' => 'INR',
        'status' => \Modules\Payments\Models\PaymentOrder::STATUS_INITIATED,
        'gateway_order_id' => 'order_stub_admfee_001',
    ]);
    $alloc->forceFill(['admission_fee_order_id' => $order->id])->save();

    $payload = [
        'event' => 'payment.captured',
        'payload' => ['payment' => ['entity' => [
            'id' => 'pay_admfee_001',
            'order_id' => 'order_stub_admfee_001',
            'amount' => 10000 * 100,
            'currency' => 'INR',
            'status' => 'captured',
            'method' => 'upi',
        ]]],
    ];
    $req = \Illuminate\Http\Request::create(
        uri: route('webhooks.gateway', ['gateway' => 'razorpay']),
        method: 'POST',
        content: json_encode($payload),
    );
    $req->headers->set('Content-Type', 'application/json');

    app(\Modules\Payments\Actions\ProcessWebhookAction::class)->execute('razorpay', $req);

    expect($alloc->fresh()->status)->toBe(SeatAllocation::STATUS_ADMITTED);
    expect($alloc->fresh()->admitted_at)->not->toBeNull();
});
