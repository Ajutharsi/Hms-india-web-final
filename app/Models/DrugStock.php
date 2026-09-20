<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DrugStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'drug_id', 'type', 'quantity',
        'balance', 'reference', 'reason',
        'performed_by', 'notes',
    ];

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
