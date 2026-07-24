<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Student\StudentDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Academics\Http\Controllers\Admin\AcademicSubjectsController;
use Modules\Academics\Http\Controllers\Admin\CoursePoolsController;
use Modules\Academics\Http\Controllers\Admin\DepartmentsController;
use Modules\Academics\Http\Controllers\Admin\ProgrammesController;
use Modules\Academics\Models\Program;
use Modules\Admissions\Http\Controllers\Admin\AdmissionRoundsController;
use Modules\Admissions\Http\Controllers\Admin\ApplicationVerificationController;
use Modules\Admissions\Http\Controllers\Admin\EligibilityRulesController;
use Modules\Admissions\Http\Controllers\Admin\NoticesController;
use Modules\Admissions\Http\Controllers\Admin\ReservationCategoriesController;
use Modules\Admissions\Http\Controllers\Admin\SessionsController;
use Modules\Admissions\Http\Controllers\Admin\SiteSettingsController;
use Modules\Admissions\Http\Controllers\Admin\WithdrawalsController;
use Modules\Admissions\Http\Controllers\Student\ApplicationController;
use Modules\Admissions\Http\Controllers\Student\WithdrawalController;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Notice;
use Modules\Audit\Http\Controllers\Admin\AuditLogController;
use Modules\Audit\Http\Controllers\Admin\DpdpConsentsController;
use Modules\Documents\Http\Controllers\Admin\DocumentQueueController;
use Modules\Documents\Http\Controllers\Student\DigilockerController;
use Modules\Documents\Http\Controllers\Student\UploadsController;
use Modules\Fees\Http\Controllers\Admin\FeesController;
use Modules\Merit\Http\Controllers\Admin\MeritListsController;
use Modules\Merit\Http\Controllers\PublicMeritController;
use Modules\Merit\Http\Controllers\Student\MeritResultController;
use Modules\Notifications\Http\Controllers\Admin\NotificationLogsController;
use Modules\Notifications\Http\Controllers\Admin\NotificationTemplatesController;
use Modules\Notifications\Http\Controllers\Admin\SmsProvidersController;
use Modules\Notifications\Http\Controllers\Admin\WhatsappProvidersController;
use Modules\Payments\Http\Controllers\Admin\GatewaysController;
use Modules\Payments\Http\Controllers\Admin\RefundPoliciesController;
use Modules\Payments\Http\Controllers\Admin\RefundsController;
use Modules\Payments\Http\Controllers\CallbackController;
use Modules\Payments\Http\Controllers\Student\PaymentController;
use Modules\Payments\Http\Controllers\WebhookController;
use Modules\Reports\Http\Controllers\Admin\ReportsController;
use Modules\Seats\Http\Controllers\Admin\SeatAllocationsController;
use Modules\Seats\Http\Controllers\Admin\SpotAdmissionsController;
use Modules\Seats\Http\Controllers\Student\AdmissionFeeController;
use Modules\Seats\Http\Controllers\Student\AllotmentController;
use Modules\Students\Http\Controllers\Student\AcademicRecordsController;
use Modules\Students\Http\Controllers\Student\ProfileController;
use Modules\Tests\Http\Controllers\Admin\AdmissionTestsController;
use Modules\Tests\Http\Controllers\Student\AdmitCardController;
use Modules\Users\Http\Controllers\Admin\UsersController;

Route::get('/merit/{programCode}/{roundNumber}', [PublicMeritController::class, 'show'])
    ->where('programCode', '[A-Z0-9]+')
    ->where('roundNumber', '[0-9]+')
    ->name('public.merit.show');

Route::get('/', function () {
    return Inertia::render('Public/Home', [
        'programmes' => Program::where('is_active', true)
            ->with('department:id,name')
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'code', 'name', 'department_id', 'type', 'intake_capacity', 'duration_years']),
        'active_session' => AcademicSession::where('is_active', true)
            ->first(['id', 'code', 'name', 'application_open_date', 'application_close_date', 'commencement_date']),
        'notices' => Notice::where('is_active', true)
            ->orderByDesc('notice_date')
            ->orderByDesc('sort_order')
            ->limit(20)
            ->get()
            ->map(fn ($n) => [
                'date' => $n->notice_date->toDateString(),
                'title' => $n->title,
                'tab' => $n->tab,
                'href' => $n->url ?: '#',
            ]),
    ]);
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route(auth()->user()->dashboardRoute()))->name('dashboard');

    // Staff share the admin panel — landing redirects there.
    Route::get('/staff', fn () => redirect()->route('admin.dashboard'))->name('staff.dashboard');
});

Route::middleware(['auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/', StudentDashboardController::class)->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/personal', [ProfileController::class, 'showPersonal'])->name('profile.personal');
        Route::post('/profile/personal', [ProfileController::class, 'updatePersonal'])->name('profile.personal.update');
        Route::get('/profile/family', [ProfileController::class, 'showFamily'])->name('profile.family');
        Route::post('/profile/family', [ProfileController::class, 'updateFamily'])->name('profile.family.update');
        Route::get('/profile/address', [ProfileController::class, 'showAddress'])->name('profile.address');
        Route::post('/profile/address', [ProfileController::class, 'updateAddress'])->name('profile.address.update');
        Route::get('/profile/other', [ProfileController::class, 'showOther'])->name('profile.other');
        Route::post('/profile/other', [ProfileController::class, 'updateOther'])->name('profile.other.update');
        Route::get('/profile/review', [ProfileController::class, 'showReview'])->name('profile.review');
        Route::post('/profile/submit', [ProfileController::class, 'finalSubmit'])->name('profile.submit');

        Route::get('/academic-records', [AcademicRecordsController::class, 'index'])->name('academic-records.index');
        Route::post('/academic-records', [AcademicRecordsController::class, 'upsert'])->name('academic-records.upsert');
        Route::post('/academic-records/entrance-exams', [AcademicRecordsController::class, 'storeEntranceExam'])->name('academic-records.entrance-exams.store');
        Route::delete('/academic-records/entrance-exams/{id}', [AcademicRecordsController::class, 'destroyEntranceExam'])->name('academic-records.entrance-exams.destroy');

        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::post('/applications', [ApplicationController::class, 'start'])->name('applications.start');
        Route::get('/applications/{application}', [ApplicationController::class, 'edit'])->name('applications.edit');
        Route::get('/applications/{application}/download', [ApplicationController::class, 'download'])->name('applications.download');
        Route::patch('/applications/{application}/draft', [ApplicationController::class, 'saveDraft'])->name('applications.draft.save');
        Route::post('/applications/{application}/submit', [ApplicationController::class, 'submit'])->name('applications.submit');

        Route::get('/uploads', [UploadsController::class, 'index'])->name('uploads.index');
        Route::post('/uploads/{type}', [UploadsController::class, 'store'])->name('uploads.store');
        Route::post('/uploads/{type}/digilocker', [UploadsController::class, 'fetchDigilocker'])->name('uploads.digilocker');

        Route::get('/applications/{application}/payment', [PaymentController::class, 'show'])->name('payments.show');
        Route::post('/applications/{application}/payment/init', [PaymentController::class, 'init'])->name('payments.init');
        Route::post('/payments/{order}/mock-pay', [PaymentController::class, 'mockPay'])->name('payments.mock-pay');

        Route::get('/admit-card/{application}', [AdmitCardController::class, 'show'])->name('admit-card.show');
        Route::get('/admit-card/{application}/download', [AdmitCardController::class, 'download'])->name('admit-card.download');

        Route::get('/applications/{application}/merit', [MeritResultController::class, 'show'])->name('merit.result');

        Route::post('/applications/{application}/withdraw', [WithdrawalController::class, 'store'])->name('applications.withdraw');

        Route::get('/allotment/{application}', [AllotmentController::class, 'show'])->name('allotment.show');
        Route::post('/allotment/{allocation}/accept', [AllotmentController::class, 'accept'])->name('allotment.accept');
        Route::post('/allotment/{allocation}/decline', [AllotmentController::class, 'decline'])->name('allotment.decline');
        Route::get('/allotment/{allocation}/admission-fee', [AdmissionFeeController::class, 'show'])->name('allotment.admission-fee.show');
        Route::post('/allotment/{allocation}/admission-fee/init', [AdmissionFeeController::class, 'init'])->name('allotment.admission-fee.init');
    });

Route::post('/webhooks/{gateway}', WebhookController::class)->name('webhooks.gateway');
Route::post('/payments/callback/razorpay', [CallbackController::class, 'razorpay'])
    ->name('payments.callback.razorpay');

Route::middleware('auth')->group(function () {
    Route::get('/digilocker/link', [DigilockerController::class, 'link'])->name('digilocker.link');
    Route::get('/digilocker/callback', [DigilockerController::class, 'callback'])->name('digilocker.callback');
    Route::post('/digilocker/unlink', [DigilockerController::class, 'unlink'])->name('digilocker.unlink');
    Route::get('/documents/{document}/download', [UploadsController::class, 'download'])->name('documents.download');
    Route::get('/documents/{document}/preview', [UploadsController::class, 'preview'])->name('documents.preview');
});

Route::middleware(['auth', 'role:super_admin|admin|staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        // ---------- Setup (super_admin / admin only — staff blocked) ----------
        Route::middleware('role:super_admin|admin')->group(function () {
            Route::get('sessions', [SessionsController::class, 'index'])->name('sessions.index');
            Route::post('sessions', [SessionsController::class, 'store'])->name('sessions.store');
            Route::patch('sessions/{session}', [SessionsController::class, 'update'])->name('sessions.update');
            Route::post('sessions/{session}/activate', [SessionsController::class, 'activate'])->name('sessions.activate');
            Route::post('sessions/{session}/archive', [SessionsController::class, 'archive'])->name('sessions.archive');
            Route::delete('sessions/{session}', [SessionsController::class, 'destroy'])->name('sessions.destroy');

            Route::get('departments', [DepartmentsController::class, 'index'])->name('departments.index');
            Route::post('departments', [DepartmentsController::class, 'store'])->name('departments.store');
            Route::patch('departments/{department}', [DepartmentsController::class, 'update'])->name('departments.update');
            Route::delete('departments/{department}', [DepartmentsController::class, 'destroy'])->name('departments.destroy');

            Route::get('programmes', [ProgrammesController::class, 'index'])->name('programmes.index');
            Route::post('programmes', [ProgrammesController::class, 'store'])->name('programmes.store');
            Route::patch('programmes/{programme}', [ProgrammesController::class, 'update'])->name('programmes.update');
            Route::delete('programmes/{programme}', [ProgrammesController::class, 'destroy'])->name('programmes.destroy');
            Route::post('programmes/{programme}/reservations', [ProgrammesController::class, 'syncReservations'])
                ->name('programmes.reservations.sync');
            Route::post('programmes/{programme}/application-fees', [ProgrammesController::class, 'syncApplicationFees'])
                ->name('programmes.application-fees.sync');
            Route::post('programmes/{programme}/admission-fees', [ProgrammesController::class, 'syncAdmissionFees'])
                ->name('programmes.admission-fees.sync');

            Route::get('academic-subjects', [AcademicSubjectsController::class, 'index'])->name('academic-subjects.index');
            Route::post('academic-subjects', [AcademicSubjectsController::class, 'store'])->name('academic-subjects.store');
            Route::patch('academic-subjects/{subject}', [AcademicSubjectsController::class, 'update'])->name('academic-subjects.update');
            Route::post('academic-subjects/{subject}/toggle', [AcademicSubjectsController::class, 'toggle'])->name('academic-subjects.toggle');
            Route::delete('academic-subjects/{subject}', [AcademicSubjectsController::class, 'destroy'])->name('academic-subjects.destroy');

            Route::get('reservation-categories', [ReservationCategoriesController::class, 'index'])->name('reservation-categories.index');
            Route::post('reservation-categories', [ReservationCategoriesController::class, 'store'])->name('reservation-categories.store');
            Route::patch('reservation-categories/{category}', [ReservationCategoriesController::class, 'update'])->name('reservation-categories.update');
            Route::delete('reservation-categories/{category}', [ReservationCategoriesController::class, 'destroy'])->name('reservation-categories.destroy');
            Route::post('reservation-categories/{category}/toggle', [ReservationCategoriesController::class, 'toggle'])->name('reservation-categories.toggle');

            Route::get('fees', [FeesController::class, 'index'])->name('fees.index');
            Route::post('fees/heads', [FeesController::class, 'storeHead'])->name('fees.heads.store');
            Route::patch('fees/heads/{head}', [FeesController::class, 'updateHead'])->name('fees.heads.update');
            Route::post('fees/structures', [FeesController::class, 'storeStructure'])->name('fees.structures.store');

            Route::get('course-pools', [CoursePoolsController::class, 'index'])->name('course-pools.index');
            Route::post('course-pools', [CoursePoolsController::class, 'store'])->name('course-pools.store');
            Route::patch('course-pools/{pool}', [CoursePoolsController::class, 'update'])->name('course-pools.update');
            Route::delete('course-pools/{pool}', [CoursePoolsController::class, 'destroy'])->name('course-pools.destroy');

            Route::get('eligibility-rules', [EligibilityRulesController::class, 'index'])->name('eligibility-rules.index');
            Route::post('eligibility-rules', [EligibilityRulesController::class, 'store'])->name('eligibility-rules.store');
            Route::patch('eligibility-rules/{rule}', [EligibilityRulesController::class, 'update'])->name('eligibility-rules.update');
            Route::delete('eligibility-rules/{rule}', [EligibilityRulesController::class, 'destroy'])->name('eligibility-rules.destroy');
            Route::post('eligibility-rules/programmes/{programme}/re-evaluate', [EligibilityRulesController::class, 'reEvaluate'])
                ->name('eligibility-rules.re-evaluate');
        });

        Route::get('applications', [ApplicationVerificationController::class, 'index'])->name('applications.index');
        Route::get('applications/{application}', [ApplicationVerificationController::class, 'show'])->name('applications.show');
        Route::post('applications/{application}/override-eligibility', [ApplicationVerificationController::class, 'override'])->name('applications.override-eligibility');

        Route::get('documents', [DocumentQueueController::class, 'index'])->name('documents.index');
        Route::post('documents/{document}/approve', [DocumentQueueController::class, 'approve'])->name('documents.approve');
        Route::post('documents/{document}/reject', [DocumentQueueController::class, 'reject'])->name('documents.reject');
        Route::post('documents/bulk-approve', [DocumentQueueController::class, 'bulkApprove'])->name('documents.bulk-approve');

        // ---------- Admin-only: payments, providers, rounds, withdrawals ----------
        Route::middleware('role:super_admin|admin')->group(function () {
            Route::get('gateways', [GatewaysController::class, 'index'])->name('gateways.index');
            Route::post('gateways', [GatewaysController::class, 'store'])->name('gateways.store');
            Route::patch('gateways/{gateway}', [GatewaysController::class, 'update'])->name('gateways.update');
            Route::post('gateways/{gateway}/toggle', [GatewaysController::class, 'toggle'])->name('gateways.toggle');

            Route::get('rounds', [AdmissionRoundsController::class, 'index'])->name('rounds.index');
            Route::post('rounds', [AdmissionRoundsController::class, 'store'])->name('rounds.store');
            Route::patch('rounds/{round}/status', [AdmissionRoundsController::class, 'updateStatus'])->name('rounds.status.update');
            Route::delete('rounds/{round}', [AdmissionRoundsController::class, 'destroy'])->name('rounds.destroy');

            Route::get('withdrawals', [WithdrawalsController::class, 'index'])->name('withdrawals.index');
            Route::post('withdrawals/{withdrawal}/approve', [WithdrawalsController::class, 'approve'])->name('withdrawals.approve');
            Route::post('withdrawals/{withdrawal}/reject', [WithdrawalsController::class, 'reject'])->name('withdrawals.reject');

            Route::get('refunds', [RefundsController::class, 'index'])->name('refunds.index');
            Route::post('refunds/{refund}/mark-paid', [RefundsController::class, 'markPaid'])->name('refunds.mark-paid');
            Route::post('refunds/{refund}/mark-failed', [RefundsController::class, 'markFailed'])->name('refunds.mark-failed');

            Route::get('refund-policies', [RefundPoliciesController::class, 'index'])->name('refund-policies.index');
            Route::post('refund-policies', [RefundPoliciesController::class, 'store'])->name('refund-policies.store');
            Route::patch('refund-policies/{policy}', [RefundPoliciesController::class, 'update'])->name('refund-policies.update');
            Route::delete('refund-policies/{policy}', [RefundPoliciesController::class, 'destroy'])->name('refund-policies.destroy');

            Route::get('sms-providers', [SmsProvidersController::class, 'index'])->name('sms-providers.index');
            Route::post('sms-providers', [SmsProvidersController::class, 'store'])->name('sms-providers.store');
            Route::patch('sms-providers/{provider}', [SmsProvidersController::class, 'update'])->name('sms-providers.update');
            Route::post('sms-providers/{provider}/toggle', [SmsProvidersController::class, 'toggle'])->name('sms-providers.toggle');
            Route::delete('sms-providers/{provider}', [SmsProvidersController::class, 'destroy'])->name('sms-providers.destroy');

            Route::get('whatsapp-providers', [WhatsappProvidersController::class, 'index'])->name('whatsapp-providers.index');
            Route::post('whatsapp-providers', [WhatsappProvidersController::class, 'store'])->name('whatsapp-providers.store');
            Route::patch('whatsapp-providers/{provider}', [WhatsappProvidersController::class, 'update'])->name('whatsapp-providers.update');
            Route::post('whatsapp-providers/{provider}/toggle', [WhatsappProvidersController::class, 'toggle'])->name('whatsapp-providers.toggle');
            Route::delete('whatsapp-providers/{provider}', [WhatsappProvidersController::class, 'destroy'])->name('whatsapp-providers.destroy');

            Route::get('notification-templates', [NotificationTemplatesController::class, 'index'])->name('notification-templates.index');
            Route::post('notification-templates', [NotificationTemplatesController::class, 'store'])->name('notification-templates.store');
            Route::patch('notification-templates/{template}', [NotificationTemplatesController::class, 'update'])->name('notification-templates.update');
            Route::post('notification-templates/{template}/toggle', [NotificationTemplatesController::class, 'toggle'])->name('notification-templates.toggle');
            Route::delete('notification-templates/{template}', [NotificationTemplatesController::class, 'destroy'])->name('notification-templates.destroy');

            Route::get('notification-logs', [NotificationLogsController::class, 'index'])->name('notification-logs.index');
        });

        // Staff can view reports list + drill-down, but export/AISHE is admin-only.
        Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
        Route::get('reports/{key}', [ReportsController::class, 'show'])->name('reports.show');
        Route::middleware('role:super_admin|admin')->group(function () {
            Route::get('reports/{key}/export', [ReportsController::class, 'export'])->name('reports.export');
            Route::get('aishe/export', [ReportsController::class, 'aishe'])->name('reports.aishe');
        });

        // Staff can view the audit log (read-only). DPDP consents stay admin-only.
        Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
        Route::middleware('role:super_admin|admin')->group(function () {
            Route::get('dpdp-consents', [DpdpConsentsController::class, 'index'])->name('dpdp-consents.index');

            Route::get('notices', [NoticesController::class, 'index'])->name('notices.index');
            Route::post('notices', [NoticesController::class, 'store'])->name('notices.store');
            Route::patch('notices/{notice}', [NoticesController::class, 'update'])->name('notices.update');
            Route::post('notices/{notice}/toggle', [NoticesController::class, 'toggle'])->name('notices.toggle');
            Route::delete('notices/{notice}', [NoticesController::class, 'destroy'])->name('notices.destroy');

            Route::get('site-settings', [SiteSettingsController::class, 'index'])->name('site-settings.index');
            Route::post('site-settings', [SiteSettingsController::class, 'update'])->name('site-settings.update');

            // Admin & staff user management
            Route::get('users', [UsersController::class, 'index'])->name('users.index');
            Route::post('users', [UsersController::class, 'store'])->name('users.store');
            Route::patch('users/{user}', [UsersController::class, 'update'])->name('users.update');
            Route::post('users/{user}/reset-password', [UsersController::class, 'resetPassword'])->name('users.reset-password');
            Route::delete('users/{user}', [UsersController::class, 'destroy'])->name('users.destroy');
        });

        Route::get('seat-allocations', [SeatAllocationsController::class, 'index'])->name('seat-allocations.index');
        Route::get('seat-allocations/{round}', [SeatAllocationsController::class, 'show'])->name('seat-allocations.show');
        Route::post('seat-allocations/{round}/generate', [SeatAllocationsController::class, 'generate'])->name('seat-allocations.generate');
        Route::post('seat-allocations/{round}/lock', [SeatAllocationsController::class, 'lockAllotment'])->name('seat-allocations.lock');

        // Spot admission overrides reserved seat caps — admin-only.
        Route::middleware('role:super_admin|admin')->group(function () {
            Route::get('spot-admission', [SpotAdmissionsController::class, 'index'])->name('spot-admission.index');
            Route::post('spot-admission', [SpotAdmissionsController::class, 'store'])->name('spot-admission.store');
        });

        // Staff can view + generate merit lists; publish + destroy locked to admin.
        Route::get('merit-lists', [MeritListsController::class, 'index'])->name('merit-lists.index');
        Route::post('rounds/{round}/merit-list/generate', [MeritListsController::class, 'generate'])->name('merit-lists.generate');
        Route::get('merit-lists/{meritList}', [MeritListsController::class, 'show'])->name('merit-lists.show');
        Route::middleware('role:super_admin|admin')->group(function () {
            Route::post('merit-lists/{meritList}/publish', [MeritListsController::class, 'publish'])->name('merit-lists.publish');
            Route::delete('merit-lists/{meritList}', [MeritListsController::class, 'destroy'])->name('merit-lists.destroy');
        });

        Route::get('admission-tests', [AdmissionTestsController::class, 'index'])->name('admission-tests.index');
        Route::post('admission-tests', [AdmissionTestsController::class, 'store'])->name('admission-tests.store');
        Route::get('admission-tests/{admissionTest}', [AdmissionTestsController::class, 'show'])->name('admission-tests.show');
        Route::patch('admission-tests/{admissionTest}', [AdmissionTestsController::class, 'update'])->name('admission-tests.update');
        Route::post('admission-tests/{admissionTest}/schedule', [AdmissionTestsController::class, 'saveSchedule'])->name('admission-tests.schedule.save');
        Route::post('admission-tests/{admissionTest}/publish-admit-cards', [AdmissionTestsController::class, 'publishAdmitCards'])->name('admission-tests.publish-admit-cards');
        Route::post('admission-tests/{admissionTest}/marks', [AdmissionTestsController::class, 'saveMarks'])->name('admission-tests.marks.save');
        Route::post('admission-tests/{admissionTest}/marks/preview', [AdmissionTestsController::class, 'previewCsv'])->name('admission-tests.marks.preview');
        Route::post('admission-tests/{admissionTest}/marks/commit', [AdmissionTestsController::class, 'commitCsv'])->name('admission-tests.marks.commit');
        Route::delete('admission-tests/{admissionTest}', [AdmissionTestsController::class, 'destroy'])->name('admission-tests.destroy');
    });
