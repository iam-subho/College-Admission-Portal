<?php

namespace Modules\Admissions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Academics\Models\Program;
use Modules\Admissions\Database\Factories\ApplicationFactory;
use Modules\Students\Models\Student;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Application extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function newFactory(): ApplicationFactory
    {
        return ApplicationFactory::new();
    }

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const VERDICT_PENDING = 'pending';

    public const VERDICT_PASS = 'pass';

    public const VERDICT_FAIL = 'fail';

    public const VERDICT_OVERRIDE_PASS = 'override_pass';

    public const VERDICT_OVERRIDE_FAIL = 'override_fail';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_COVERED = 'covered';

    public const PAYMENT_NOT_REQUIRED = 'not_required';

    protected $fillable = [
        'student_id',
        'program_id',
        'academic_session_id',
        'application_number',
        'serial',
        'status',
        'draft_data',
        'submitted_at',
        'status_changed_at',
        'eligibility_verdict',
        'eligibility_reasons',
        'eligibility_decided_by',
        'eligibility_decided_at',
        'eligibility_remark',
        'declaration_anti_ragging',
        'declaration_information_true',
        'special_request',
        'covered_by_payment_order_id',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'draft_data' => 'array',
            'eligibility_reasons' => 'array',
            'submitted_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'eligibility_decided_at' => 'datetime',
            'declaration_anti_ragging' => 'boolean',
            'declaration_information_true' => 'boolean',
        ];
    }

    public function courseSelections(): HasMany
    {
        return $this->hasMany(ApplicationCourseSelection::class);
    }

    public function paymentOrders(): HasMany
    {
        return $this->hasMany(\Modules\Payments\Models\PaymentOrder::class);
    }

    public function coveredByOrder(): BelongsTo
    {
        return $this->belongsTo(\Modules\Payments\Models\PaymentOrder::class, 'covered_by_payment_order_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function eligibilityDecidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eligibility_decided_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'eligibility_verdict', 'eligibility_remark', 'submitted_at', 'payment_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('application');
    }

    public function isPaymentComplete(): bool
    {
        return in_array($this->payment_status, [
            self::PAYMENT_PAID,
            self::PAYMENT_COVERED,
            self::PAYMENT_NOT_REQUIRED,
        ], true);
    }
}
