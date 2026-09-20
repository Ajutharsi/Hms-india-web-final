<?php

namespace App\Support;

class DataTableFormat
{
    public static function patientInfo($patient): string
    {
        return '<div><strong>' . e($patient->name ?? 'N/A') . '</strong><br><small class="text-muted">'
            . e($patient->mrn ?? 'N/A') . '</small></div>';
    }

    
    public static function doctorInfo($doctor, bool $prefixDr = false): string
    {
        if (!$doctor) {
            return '<div><strong>N/A</strong></div>';
        }

        $name = ($prefixDr ? 'Dr. ' : '') . e($doctor->name);
        $sub  = $doctor->specialization ? '<br><small class="text-muted">' . e($doctor->specialization) . '</small>' : '';

        return '<div class="d-flex align-items-center gap-2">'
            . '<img src="' . e($doctor->photo_url) . '" alt="" class="rounded-circle" style="width:28px;height:28px;object-fit:cover;flex-shrink:0;" '
            . 'onerror="this.onerror=null;this.src=\'' . e(asset('images/defaults/doctor.png')) . '\';" />'
            . '<div><strong>' . $name . '</strong>' . $sub . '</div>'
            . '</div>';
    }

    public static function badge(?string $value, array $colorMap, string $fallback = 'secondary'): string
    {
        $value = (string) $value;

        return '<span class="badge bg-label-' . ($colorMap[$value] ?? $fallback) . '">' . e(ucfirst($value)) . '</span>';
    }

    public static function money($amount): string
    {
        return config('hospital.currency', 'LKR') . ' ' . number_format((float) $amount, 2);
    }

    
    public static function patientFilter($query, string $keyword): void
    {
        $query->whereHas('patient', function ($p) use ($keyword) {
            $p->where('mrn', 'like', "{$keyword}%")
              ->when(\App\Models\Patient::hashFor($keyword), fn($p2, $hash) => $p2->orWhere('name_hash', $hash));
        });
    }
}
