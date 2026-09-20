<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BloodUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_no', 'blood_group', 'component', 'donor_name',
        'collection_date', 'expiry_date', 'status', 'reserved_for', 'notes',
    ];

    protected $casts = [
        'collection_date' => 'date',
        'expiry_date'     => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($unit) {
            $year  = date('Y');
            $last  = static::whereYear('created_at', $year)->latest('id')->first();
            $count = $last ? (int) substr($last->unit_no, -4) + 1 : 1;
            $unit->unit_no = 'BLD-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    public function reservedForPatient()
    {
        return $this->belongsTo(Patient::class, 'reserved_for');
    }

    public function transfusions()
    {
        return $this->hasMany(BloodTransfusion::class);
    }

    public function isNearExpiry(int $days = 7): bool
    {
        return $this->expiry_date && now()->diffInDays($this->expiry_date, false) <= $days && now()->diffInDays($this->expiry_date, false) >= 0;
    }
}
