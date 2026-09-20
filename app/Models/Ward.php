<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'ward_no', 'name', 'type', 'floor', 'building',
        'total_beds', 'available_beds', 'charge_per_day',
        'status', 'description',
    ];

    public function beds()
    {
        return $this->hasMany(Bed::class);
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }

    // Auto generate ward_no
   protected static function boot()
{
    parent::boot();
    static::creating(function ($ward) {
        $last   = static::latest('id')->first();
        $number = $last ? $last->id + 1 : 1;
        $ward->ward_no = 'WRD-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    });

    static::created(function ($ward) {
        for ($i = 1; $i <= $ward->total_beds; $i++) {
            Bed::create([
                'bed_no'  => $ward->ward_no . '-B' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'ward_id' => $ward->id,
                'type'    => 'standard',
                'status'  => 'available',
            ]);
        }
    });
}

    /** Scope: active records. */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
