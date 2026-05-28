<?php

namespace Modules\Students\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\ReservationCategory;
use Modules\Students\Database\Factories\StudentFactory;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'registration_number',
        'aadhaar_encrypted',
        'aadhaar_last4',
        'aadhaar_full_name',
        'abc_id',
        'gender',
        'dob',
        'nationality',
        'foreign_national',
        'reservation_category_id',
        'sub_caste',
        'religion',
        'is_minority',
        'mother_tongue',
        'blood_group',
        'father_name',
        'father_occupation',
        'father_qualification',
        'father_income',
        'father_mobile',
        'father_email',
        'mother_name',
        'mother_occupation',
        'mother_qualification',
        'mother_income',
        'mother_mobile',
        'mother_email',
        'guardian_mobile',
        'annual_family_income',
        'siblings_count',
        'family_in_govt_service',
        'is_single_parent',
        'is_first_generation_graduate',
        'emergency_contact',
        'address',
        'house_no',
        'locality',
        'country',
        'state',
        'district',
        'taluka',
        'pincode',
        'domicile_state',
        'domicile_district',
        'correspondence_same_as_permanent',
        'correspondence_house_no',
        'correspondence_locality',
        'correspondence_taluka',
        'correspondence_district',
        'correspondence_state',
        'correspondence_country',
        'correspondence_pincode',
        'category_certificate_no',
        'category_cert_issuer',
        'category_cert_date',
        'category_cert_validity_year',
        'income_certificate_no',
        'pwd_type',
        'pwd_percentage',
        'udid_number',
        'ncc_certificate',
        'is_nss_volunteer',
        'sports_level',
        'awards',
        'accommodation',
        'transport_required',
        'communication_preference',
        'profile_completion',
        'profile_locked',
        'profile_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'category_cert_date' => 'date',
            'is_minority' => 'boolean',
            'foreign_national' => 'boolean',
            'family_in_govt_service' => 'boolean',
            'is_single_parent' => 'boolean',
            'is_first_generation_graduate' => 'boolean',
            'is_nss_volunteer' => 'boolean',
            'transport_required' => 'boolean',
            'correspondence_same_as_permanent' => 'boolean',
            'profile_locked' => 'boolean',
            'profile_completed_at' => 'datetime',
            'annual_family_income' => 'decimal:2',
            'father_income' => 'decimal:2',
            'mother_income' => 'decimal:2',
            'pwd_percentage' => 'decimal:2',
            'siblings_count' => 'integer',
        ];
    }

    protected $hidden = ['aadhaar_encrypted'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReservationCategory::class, 'reservation_category_id');
    }

    public function academicRecords(): HasMany
    {
        return $this->hasMany(StudentAcademicRecord::class);
    }

    public function entranceExams(): HasMany
    {
        return $this->hasMany(StudentEntranceExam::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    protected static function newFactory(): StudentFactory
    {
        return StudentFactory::new();
    }
}
