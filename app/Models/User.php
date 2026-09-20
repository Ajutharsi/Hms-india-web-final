<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasRoles;
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /** Always present in array/JSON output (e.g. the staff edit-offcanvas fetch). */
    protected $appends = ['profile_photo_url'];

protected $fillable = [
    'name', 'email', 'password', 'phone', 'hospital_id',
    'employee_id', 'department_id', 'joining_date', 'dob',
    'gender', 'blood_group', 'national_id', 'address',
    'employment_type', 'shift', 'qualification', 'license_no',
    'experience_years', 'pay_type', 'pay_rate', 'currency',
    'allowance_transport', 'allowance_meal', 'allowance_housing',
    'bank_account_name', 'bank_account_no', 'bank_name', 'payment_method',
    'employment_status', 'leave_balance',
    'emergency_contact_name', 'emergency_contact_phone', 'nok_relationship',
    'profile_photo',
    'doc_license', 'doc_certificate', 'doc_national_id', 'doc_other','last_login',
    'must_change_password',
];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

   protected function casts(): array
{
    return [
        'email_verified_at'     => 'datetime',
        'password'              => 'hashed',
        'last_login'            => 'datetime',
        'must_change_password'  => 'boolean',
    ];
}
    public function doctor()
    {
        return $this->hasOne(Doctor::class, 'email', 'email');
    }

    /** The hospital this (typically admin-role) user administers. See SUPER_ADMIN_IMPERSONATION_REPORT.md. */
    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

  
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->profile_photo && Storage::disk('public')->exists($this->profile_photo)) {
            return asset('storage/' . $this->profile_photo);
        }

        return null;
    }

    public function department()
{
    return $this->belongsTo(\App\Models\Department::class);
}
}
