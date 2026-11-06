<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A vendor the hospital buys stock from.
 *
 * Purchase orders store the supplier's name as text rather than a
 * foreign key, which is how they were written; this model is the master
 * list that keeps those names consistent and holds the contact details
 * nobody had anywhere.
 */
class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'contact_person', 'phone', 'email',
        'address', 'gst_no', 'payment_terms', 'status', 'notes',
    ];

    /** Orders raised against this supplier, matched by the name they store. */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier', 'name');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
