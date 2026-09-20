<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;


class MaintenanceCentreService
{
    private const MODULE = 'maintenance';

    /** Log lines matching these keys have their value masked, regardless of format (key: value, key=value, "key":"value"). */
    private const SENSITIVE_LOG_PATTERN = '/("?(?:password|secret|token|api[_-]?key|private[_-]?key|authorization)"?\s*[:=]\s*)("?)([^"\s,}]+)("?)/i';

    /** Bounded tail read — never load the whole log file. Matches SystemHealthService::recentErrors()'s own principle. */
    private const LOG_READ_BYTES = 2 * 1024 * 1024; // 2 MB

    public function __construct(private readonly SystemHealthService $health)
    {
    }

    public function dashboardStatus(): array
    {
        $h = $this->health->all();

        return [
            'application' => $h['application'],
            'database'    => $h['database'],
            'cache'       => $h['cache'],
            'queue'       => $h['queue'],
            'storage'     => $h['storage'],
            'backups'     => $h['backups'],
            'scheduler'   => $h['scheduler'],
            'alerts'      => $h['alerts'],
            'isDownForMaintenance' => app()->isDownForMaintenance(),
        ];
    }

    // ==================== Cache Management ====================

    public const CACHE_TYPES = ['application' => 'cache:clear', 'config' => 'config:clear', 'route' => 'route:clear', 'view' => 'view:clear'];

    public function clearCache(string $type): void
    {
        if (! array_key_exists($type, self::CACHE_TYPES)) {
            throw new \InvalidArgumentException("Unknown cache type [{$type}].");
        }

        Artisan::call(self::CACHE_TYPES[$type]);

        ActivityLog::log('cache_cleared', self::MODULE, auth()->user()->name . " cleared the {$type} cache");
    }

    // ==================== Queue Management ====================

    public function failedJobsList()
    {
        return DB::table('failed_jobs')->orderByDesc('failed_at')->limit(200)->get(['id', 'uuid', 'connection', 'queue', 'exception', 'failed_at']);
    }

    
    public function retryFailedJobs(): int
    {
        $count = DB::table('failed_jobs')->count();

        if ($count > 0) {
            try {
                Artisan::call('queue:retry', ['id' => ['all']]);
            } catch (\Throwable $e) {
                ActivityLog::log('queue_retry_failed', self::MODULE, auth()->user()->name . " attempted to retry {$count} failed job(s), but it failed: " . $e->getMessage());

                throw $e;
            }
        }

        ActivityLog::log('queue_retried', self::MODULE, auth()->user()->name . " retried {$count} failed job(s)");

        return $count;
    }

    public function clearFailedJobs(): int
    {
        $count = DB::table('failed_jobs')->count();

        Artisan::call('queue:flush');

        ActivityLog::log('queue_cleared', self::MODULE, auth()->user()->name . " cleared {$count} failed job(s)");

        return $count;
    }

    // ==================== Database Management (read-only) ====================

    public function migrationStatus(): array
    {
        return $this->health->database()['migrations'];
    }

    
    public function logEntries(array $filters): array
    {
        $path = storage_path('logs/laravel.log');
        if (! is_file($path)) {
            return [];
        }

        $size = filesize($path);
        $bytesToRead = min($size, self::LOG_READ_BYTES);

        $handle = fopen($path, 'r');
        fseek($handle, -$bytesToRead, SEEK_END);
        $chunk = fread($handle, $bytesToRead);
        fclose($handle);

        $lines = explode("\n", $chunk);
        // The read may start mid-line (bounded tail) — drop a leading partial line.
        if ($size > self::LOG_READ_BYTES) {
            array_shift($lines);
        }

        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\S+)\.(\w+): (.*)$/', $line, $m)) {
                if ($current) {
                    $entries[] = $current;
                }
                $current = ['timestamp' => $m[1], 'channel' => $m[2], 'level' => strtoupper($m[3]), 'message' => $m[4]];
            } elseif ($current !== null && $line !== '') {
                $current['message'] .= "\n" . $line;
            }
        }
        if ($current) {
            $entries[] = $current;
        }

        $entries = array_reverse($entries);

        $entries = array_values(array_filter($entries, function ($e) use ($filters) {
            if (! empty($filters['level']) && strtoupper($filters['level']) !== $e['level']) {
                return false;
            }
            if (! empty($filters['from_date']) && substr($e['timestamp'], 0, 10) < $filters['from_date']) {
                return false;
            }
            if (! empty($filters['to_date']) && substr($e['timestamp'], 0, 10) > $filters['to_date']) {
                return false;
            }
            if (! empty($filters['search']) && stripos($e['message'], $filters['search']) === false && stripos($e['channel'], $filters['search']) === false) {
                return false;
            }

            return true;
        }));

        return array_map(function ($e) {
            $e['message'] = preg_replace(self::SENSITIVE_LOG_PATTERN, '$1$2••••••••$4', $e['message']);
            // Bound each individual entry's display length too — a huge stack
            // trace shouldn't blow up the response for a list view.
            if (strlen($e['message']) > 2000) {
                $e['message'] = substr($e['message'], 0, 2000) . "\n… (truncated)";
            }

            return $e;
        }, $entries);
    }

    public function logExport(string $format, int $rowCount): void
    {
        ActivityLog::log('logs_exported', self::MODULE, auth()->user()->name . " exported application logs ({$format}), {$rowCount} row(s)");
    }
}
