<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequenceNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class LabTest extends Model
{
    use GeneratesSequenceNumber;
    use HasFactory;

    protected $fillable = [
        'test_code', 'test_name', 'category',
        'sample_type', 'price', 'unit',
        'normal_range_male', 'normal_range_female',
        'description', 'status',
    ];

    public function orderItems()
    {
        return $this->hasMany(LabOrderItem::class);
    }

    public function results()
    {
        return $this->hasMany(LabResult::class);
    }

    protected static function boot()
{
    parent::boot();
    static::creating(function ($model) {
        $model->test_code = static::nextSequenceNumber('test_code', 'LT-');
    });
}
}
