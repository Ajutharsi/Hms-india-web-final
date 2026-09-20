<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'item_name', 'item_type',
        'description', 'quantity', 'unit_price',
        'discount', 'total',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
