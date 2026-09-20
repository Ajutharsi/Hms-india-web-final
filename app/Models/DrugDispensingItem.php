<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugDispensingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'dispensing_id', 'drug_id', 'medicine_name',
        'quantity_prescribed', 'quantity_dispensed',
        'unit_price', 'total_price',
    ];

    public function dispensing()
    {
        return $this->belongsTo(DrugDispensing::class);
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }
}
