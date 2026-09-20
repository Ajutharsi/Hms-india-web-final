<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\PlatformAnalyticsService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function __construct(private readonly PlatformAnalyticsService $analytics)
    {
    }

    public function index()
    {
        return view('super-admin.analytics.index', $this->analytics->all());
    }

    /**
     * Same view rendered standalone for the browser's own print-to-PDF —
     * the exact convention ReportController's *Pdf() actions already use
     * (barryvdh/laravel-dompdf is installed but has zero real usage
     * anywhere in the app; this doesn't invent a second export mechanism).
     */
    public function pdf()
    {
        ActivityLog::log('analytics_exported', 'analytics', auth()->user()->name . ' exported Platform Analytics (PDF)');

        return view('super-admin.analytics.index', $this->analytics->all());
    }

    /** Minimal CSV export of the overview figures — plain fputcsv streaming, no new export framework. */
    public function exportCsv(): StreamedResponse
    {
        $data = $this->analytics->all();

        ActivityLog::log('analytics_exported', 'analytics', auth()->user()->name . ' exported Platform Analytics (CSV)');

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Section', 'Metric', 'Value']);

            foreach ([
                'Platform Overview' => $data['overview'],
                'Revenue Analytics' => $data['revenue'],
                'Subscription Analytics' => $data['subscriptions'],
                'Usage Analytics' => $data['usage'],
                'Registration Analytics' => [
                    'newHospitals30d' => $data['registrations']['newHospitals30d'],
                    'newUsers30d'     => $data['registrations']['newUsers30d'],
                ],
            ] as $section => $metrics) {
                foreach ($metrics as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        continue;
                    }
                    fputcsv($out, [$section, $key, $value ?? 'Not tracked yet']);
                }
            }

            fclose($out);
        }, 'platform-analytics-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
