<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One record per CSV/PDF/print generation from the Reports & Export Centre.
 * See SUPER_ADMIN_REPORTS_CENTRE_REPORT.md.
 */
class ReportExport extends Model
{
    use HasFactory;

    public const FORMATS = ['csv', 'pdf', 'print'];

    protected $fillable = [
        'report_slug', 'report_label', 'category', 'format',
        'filters', 'row_count', 'generated_by',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
