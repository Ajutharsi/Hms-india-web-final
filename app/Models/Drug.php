<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequenceNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Drug extends Model
{
    use GeneratesSequenceNumber;
    use HasFactory;

    protected $fillable = [
        'drug_code', 'drug_name', 'generic_name', 'inventory_item_id',
        'category', 'form', 'strength',
        'manufacturer', 'unit_price', 'min_stock_level',
        'current_stock', 'expiry_date', 'status',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->drug_code = static::nextSequenceNumber('drug_code', 'DRG-');
        });
    }

    public function stocks()
    {
        return $this->hasMany(DrugStock::class);
    }

    public function dispensingItems()
    {
        return $this->hasMany(DrugDispensingItem::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    // Low stock check
    public function isLowStock()
    {
        return $this->current_stock <= $this->min_stock_level;
    }

    // Expiry check — within the configured near-expiry window
    public function isNearExpiry()
    {
        $nearExpiryDays = config('hospital.near_expiry', 30);

        return $this->expiry_date
            && !$this->expiry_date->isPast()
            && $this->expiry_date->diffInDays(now()) <= $nearExpiryDays;
    }

    // Expired check
    public function isExpired()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /** Scope: active records. */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
