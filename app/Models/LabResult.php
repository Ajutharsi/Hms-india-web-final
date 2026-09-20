<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class LabResult extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'lab_order_id', 'lab_order_item_id', 'patient_id',
        'lab_test_id', 'result_value', 'unit',
        'normal_range', 'flag', 'remarks',
        'performed_by', 'result_date',
    ];

    public function labOrder()
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function labOrderItem()
    {
        return $this->belongsTo(LabOrderItem::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
