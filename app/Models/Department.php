<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Department extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'code', 'description',
        'head_doctor', 'phone', 'location', 'status',
    ];

    public function patients()
    {
        return $this->hasMany(Patient::class, 'department_id');
    }

    /** Scope: active records. */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
