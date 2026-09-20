<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\MaintenanceCentreService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Super Admin Platform Maintenance Centre — see SUPER_ADMIN_PLATFORM_MAINTENANCE_REPORT.md.
 * Reuses SystemHealthService (via MaintenanceCentreService) for every
 * read-only health figure, and GlobalSettingsController's existing
 * maintenance-mode enable/disable routes directly — this controller adds
 * only the genuinely new actions (cache clearing, queue retry/clear, log
 * viewing) that didn't exist anywhere in the app before.
 */
class MaintenanceCentreController extends Controller
{
    public function __construct(private readonly MaintenanceCentreService $maintenance)
    {
    }

    public function index()
    {
        return view('super-admin.maintenance.index', [
            'status' => $this->maintenance->dashboardStatus(),
        ]);
    }

    // ==================== Cache Management ====================

    public function clearCache(Request $request, string $type)
    {
        abort_unless(array_key_exists($type, MaintenanceCentreService::CACHE_TYPES), 404);

        $this->maintenance->clearCache($type);

        return back()->with('success', ucfirst($type) . ' cache cleared.');
    }

    // ==================== Queue Management ====================

    public function failedJobsData()
    {
        return response()->json(['data' => $this->maintenance->failedJobsList()]);
    }

    public function retryFailedJobs()
    {
        try {
            $count = $this->maintenance->retryFailedJobs();
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not retry failed jobs: ' . $e->getMessage());
        }

        return back()->with('success', "Queued {$count} failed job(s) for retry.");
    }

    public function clearFailedJobs()
    {
        $count = $this->maintenance->clearFailedJobs();

        return back()->with('success', "Cleared {$count} failed job(s).");
    }

    // ==================== Log Viewer ====================

    private function logFilters(Request $request): array
    {
        return array_filter(
            $request->only(['level', 'from_date', 'to_date', 'search']),
            fn ($v) => $v !== null && $v !== ''
        );
    }

    public function logs()
    {
        return view('super-admin.maintenance.logs');
    }

    public function logsData(Request $request)
    {
        return response()->json(['data' => $this->maintenance->logEntries($this->logFilters($request))]);
    }

    public function logsExportCsv(Request $request): StreamedResponse
    {
        $entries = $this->maintenance->logEntries($this->logFilters($request));
        $this->maintenance->logExport('csv', count($entries));

        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Timestamp', 'Level', 'Channel', 'Message']);
            foreach ($entries as $e) {
                fputcsv($out, [$e['timestamp'], $e['level'], $e['channel'], $e['message']]);
            }
            fclose($out);
        }, 'application-logs-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function logsPrint(Request $request)
    {
        $entries = $this->maintenance->logEntries($this->logFilters($request));
        $this->maintenance->logExport('print', count($entries));

        return view('super-admin.maintenance.logs-print', ['entries' => $entries]);
    }
}
