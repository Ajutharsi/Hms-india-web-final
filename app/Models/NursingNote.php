<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NursingNote extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'written_by', 'admission_id',
        'note_type', 'shift', 'note', 'priority', 'noted_at',
    ];

    protected $casts = [
        'noted_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function writtenBy()
    {
        return $this->belongsTo(User::class, 'written_by');
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }
}
