<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_no', 'supplier', 'raised_by', 'total_amount', 'status',
        'order_date', 'expected_date', 'received_date', 'notes',
    ];

    protected $casts = [
        'order_date'    => 'date',
        'expected_date' => 'date',
        'received_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($po) {
            $year  = date('Y');
            $last  = static::whereYear('created_at', $year)->latest('id')->first();
            $count = $last ? (int) substr($last->po_no, -4) + 1 : 1;
            $po->po_no = 'PO-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function raisedBy()
    {
        return $this->belongsTo(User::class, 'raised_by');
    }
}
