<?php

namespace Modules\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Modules\Admissions\Models\Application;
use Modules\Documents\Database\Factories\UploadedDocumentFactory;
use Modules\Students\Models\Student;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UploadedDocument extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_DIGILOCKER = 'digilocker';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RESUBMIT = 'resubmit';

    protected $fillable = [
        'student_id',
        'application_id',
        'document_type_id',
        'disk',
        'path',
        'original_name',
        'mime',
        'size_bytes',
        'checksum_sha256',
        'source',
        'digilocker_uri',
        'digilocker_issued_by',
        'digilocker_pulled_at',
        'status',
        'rejection_reason',
        'status_changed_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'digilocker_pulled_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(DocumentVerification::class);
    }

    public function storage(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($this->disk);
    }

    public function exists(): bool
    {
        return $this->storage()->exists($this->path);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'rejection_reason', 'disk', 'path'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('document');
    }

    protected static function newFactory(): UploadedDocumentFactory
    {
        return UploadedDocumentFactory::new();
    }
}
