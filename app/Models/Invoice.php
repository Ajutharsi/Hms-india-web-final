<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'invoice_no', 'patient_id', 'appointment_id', 'admission_id',
        'invoice_date', 'due_date', 'subtotal',
        'discount', 'tax', 'total', 'paid_amount',
        'balance', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'subtotal'     => 'decimal:2',
        'discount'     => 'decimal:2',
        'tax'          => 'decimal:2',
        'total'        => 'decimal:2',
        'paid_amount'  => 'decimal:2',
        'balance'      => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $year = date('Y');
            // withTrashed so soft-deleted invoices still reserve their number.
            $count = static::withTrashed()->whereYear('created_at', $year)->count() + 1;
            $model->invoice_no = 'INV-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Recompute paid_amount / balance / status from the payments ledger.
     * Single source of truth for invoice settlement state.
     */
    public function recalculate(): self
    {
        $paid    = (float) $this->payments()->sum('amount');
        $balance = (float) $this->total - $paid;

        $this->update([
            'paid_amount' => $paid,
            'balance'     => max(0, $balance),
            'status'      => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : $this->status),
        ]);

        return $this;
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
