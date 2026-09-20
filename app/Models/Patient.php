<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Patient extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** Always present in array/JSON output (e.g. the edit-offcanvas fetch) — see getPhotoUrlAttribute(). */
    protected $appends = ['photo_url'];

    protected $fillable = [
        'user_id',
        'mrn',
        'name',
        'phone',
        'email',
        'dob',
        'gender',
        'blood_group',
        'address',
        'emergency_contact',
        'emergency_contact_name',
        'nationality',
        'marital_status',
        'occupation',
        'photo',
        'status',
        'patient_type',
    ];

    // name_hash/phone_hash/email_hash are intentionally excluded from $fillable —
    // they're derived server-side in the saving() hook below, never from user input.
    protected $hidden = [
        'name_hash',
        'phone_hash',
        'email_hash',
    ];

    protected $casts = [
        'name'                   => 'encrypted',
        'phone'                  => 'encrypted',
        'email'                  => 'encrypted',
        'dob'                    => 'encrypted',
        'blood_group'            => 'encrypted',
        'address'                => 'encrypted',
        'emergency_contact'      => 'encrypted',
        'emergency_contact_name' => 'encrypted',
    ];

    /**
     * Blind index for exact-match search on an encrypted field.
     * Same normalization must be used when hashing search input.
     */
    public static function hashFor(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : hash('sha256', strtolower($value));
    }

    /** Scope: active patients (replaces where('status','active') repeated across controllers). */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($patient) {
            $patient->mrn = self::generateMRN();
        });

        static::saving(function ($patient) {
            if ($patient->isDirty('name')) {
                $patient->name_hash = self::hashFor($patient->name);
            }
            if ($patient->isDirty('phone')) {
                $patient->phone_hash = self::hashFor($patient->phone);
            }
            if ($patient->isDirty('email')) {
                $patient->email_hash = self::hashFor($patient->email);
            }
        });
    }

   public static function generateMRN(): string
{
    $prefix = config('hospital.mrn_prefix', 'HMS');
    $year = date('Y');
    do {
        $last  = self::withTrashed()->whereYear('created_at', $year)->latest('id')->first();
        $count = $last ? (int) substr($last->mrn, -4) + 1 : 1;
        $mrn   = $prefix . '-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    } while (self::withTrashed()->where('mrn', $mrn)->exists());

    return $mrn;
}

    public function getAgeAttribute(): string
    {
        if (!$this->dob) return 'N/A';
        return \Carbon\Carbon::parse($this->dob)->age . ' yrs';
    }

    /**
     * Single source of truth for resolving a patient's photo everywhere in the
     * app. Handles three cases so nothing ever renders a broken <img>:
     *  - 'assets/...'  → legacy stock photos seeded directly under public/ (not the storage disk)
     *  - anything else → a real upload/avatar on the storage disk (storage/app/public/patients, ...)
     *  - missing file or no photo at all → the default avatar
     */
    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo) {
            if (str_starts_with($this->photo, 'assets/')) {
                if (file_exists(public_path($this->photo))) {
                    return asset($this->photo);
                }
            } elseif (Storage::disk('public')->exists($this->photo)) {
                // asset('storage/...'), not Storage::url(), to match the same
                // absolute-URL convention DoctorController's photo_url already uses.
                return asset('storage/' . $this->photo);
            }
        }

        return asset('images/defaults/patient.png');
    }

    public function medicalRecords()
{
    return $this->hasMany(PatientMedicalRecord::class, 'patient_id');
}

public function allergies()
{
    return $this->hasMany(PatientAllergy::class, 'patient_id');
}

public function problems()
{
    return $this->hasMany(PatientProblem::class, 'patient_id');
}

public function attachments()
{
    return $this->hasMany(PatientAttachment::class, 'patient_id');
}

public function currentAdmission()
{
    return $this->hasOne(\App\Models\Admission::class)->where('status', 'admitted')->latest();
}
public function dietPlan()
{
    return $this->hasOne(DietPlan::class)->where('status', 'active')->latest();
}

public function foodLogs()
{
    return $this->hasMany(FoodLog::class);
}

public function todayFoodLogs()
{
    return $this->hasMany(FoodLog::class)->whereDate('log_date', today());
}
public function fluidLogs()
{
    return $this->hasMany(FluidLog::class);
}

public function todayFluidLogs()
{
    return $this->hasMany(FluidLog::class)->whereDate('log_date', today());
}

public function staffRounds()
{
    return $this->hasMany(StaffRound::class);
}

public function lastRound()
{
    return $this->hasOne(StaffRound::class)->latest('round_time');
}
public function user()
{
    return $this->belongsTo(User::class);
}

public function vitals()
{
    return $this->hasMany(Vital::class, 'patient_id');
}

public function medicationAdministrations()
{
    return $this->hasMany(MedicationAdministration::class, 'patient_id');
}

public function nursingNotes()
{
    return $this->hasMany(NursingNote::class, 'patient_id');
}

public function radiologyOrders()
{
    return $this->hasMany(RadiologyOrder::class, 'patient_id');
}

public function insuranceClaims()
{
    return $this->hasMany(InsuranceClaim::class, 'patient_id');
}

public function ipdDeposits()
{
    return $this->hasMany(IpdDeposit::class, 'patient_id');
}

public function otSchedules()
{
    return $this->hasMany(OtSchedule::class, 'patient_id');
}

public function icuCharts()
{
    return $this->hasMany(IcuChart::class, 'patient_id');
}

public function bloodTransfusions()
{
    return $this->hasMany(BloodTransfusion::class, 'patient_id');
}

public function schemeBillings()
{
    return $this->hasMany(SchemeBilling::class, 'patient_id');
}
}
