<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Doctor extends Model
{
    use HasFactory;

    /** Always present in array/JSON output (DataTables rows, edit-offcanvas fetch) — see getPhotoUrlAttribute(). */
    protected $appends = ['photo_url'];

    protected $fillable = [
    'doctor_id', 'name', 'specialization',
    'department', 'qualification', 'registration_number',
    'phone', 'email',
        'photo', 'consultation_fee', 'status',
    'license_file', 'license_file_name', 'license_expiry',
    'certificate_file', 'certificate_file_name',
    'id_file', 'id_file_name',
];

    public function getRouteKeyName()
{
    return 'id';
}

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    // Auto generate doctor_id
   protected static function boot()
{
    parent::boot();
    static::creating(function ($doctor) {
        $prefix = config('hospital.doctor_prefix', 'DR');
        $last   = static::latest('id')->first();
        $number = $last ? $last->id + 1 : 1;
        do {
            $doctorId = $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            $number++;
        } while (static::where('doctor_id', $doctorId)->exists());

        $doctor->doctor_id = $doctorId;
    });
}

    /** Scope: active records. */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Single source of truth for resolving a doctor's photo everywhere in the
     * app — mirrors Patient::getPhotoUrlAttribute(). Handles two cases so
     * nothing ever renders a broken <img>:
     *  - 'assets/...'  → legacy/seed stock photos placed directly under public/ (not the storage disk)
     *  - anything else → a real upload on the storage disk (storage/app/public/doctors/photos/...)
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
                return asset('storage/' . $this->photo);
            }
        }

        return asset('images/defaults/doctor.png');
    }
}
