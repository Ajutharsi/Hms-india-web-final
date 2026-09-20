<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LabOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_order_id', 'lab_test_id', 'status',
    ];

    public function labOrder()
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }

    public function result()
    {
        return $this->hasOne(LabResult::class);
    }
}
