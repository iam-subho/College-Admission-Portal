<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Users\Models\DpdpConsent;
use Modules\Users\Models\OtpCode;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'mobile', 'password', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    public function dpdpConsents(): HasMany
    {
        return $this->hasMany(DpdpConsent::class);
    }

    public function dashboardRoute(): string
    {
        if ($this->hasAnyRole(['super_admin', 'admin'])) {
            return 'admin.dashboard';
        }
        if ($this->hasRole('staff')) {
            return 'staff.dashboard';
        }

        return 'student.dashboard';
    }
}
