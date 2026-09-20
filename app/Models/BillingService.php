<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequenceNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BillingService extends Model
{
    use GeneratesSequenceNumber;
    use HasFactory;

    protected $fillable = [
        'service_code', 'service_name',
        'category', 'price', 'description', 'status',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->service_code = static::nextSequenceNumber('service_code', 'SVC-');
        });
    }

    /** Scope: active records. */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
