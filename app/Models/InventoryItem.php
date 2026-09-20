<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code', 'item_name', 'category', 'unit', 'current_stock',
        'min_stock_level', 'unit_price', 'supplier', 'location', 'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (!$item->item_code) {
                $last  = static::latest('id')->first();
                $count = $last ? $last->id + 1 : 1;
                $item->item_code = 'ITM-' . str_pad($count, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock_level;
    }
}
