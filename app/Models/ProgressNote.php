<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A ward-round note on an admitted patient, in SOAP form.
 */
class ProgressNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id', 'patient_id', 'author_id', 'note_type',
        'subjective', 'objective', 'assessment', 'plan', 'noted_at',
    ];

    protected $casts = [
        'noted_at' => 'datetime',
    ];

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** The one line a list shows: the plan if there is one, else the assessment. */
    public function getHeadlineAttribute(): ?string
    {
        return $this->plan ?: ($this->assessment ?: $this->subjective);
    }
}
