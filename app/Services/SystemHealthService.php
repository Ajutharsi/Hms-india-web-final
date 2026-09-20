<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;


class SystemHealthService
{
    private const CACHE_TTL = 120;

    public function application(): array
    {
        return [
            'laravelVersion' => app()->version(),
            'phpVersion'     => phpversion(),
            'environment'    => app()->environment(),
            'debugMode'      => (bool) config('app.debug'),
        ];
    }

    public function database(): array
    {
        return Cache::remember('health.database', self::CACHE_TTL, function () {
            $connected = true;
            try {
                DB::connection()->getPdo();
            } catch (\Throwable) {
                $connected = false;
            }

            $size = null;
            if ($connected) {
                try {
                    $size = DB::select(
                        "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = DATABASE()"
                    )[0]->size ?? null;
                } catch (\Throwable) {
                    $size = null; // non-MySQL driver
                }
            }

            return [
                'connected'      => $connected,
                'connectionName' => config('database.default'),
                'sizeMb'         => $size !== null ? (float) $size : null,
                'migrations'     => $this->migrationStatus(),
            ];
        });
    }

    /** Real file-vs-ran-row diff — no Artisan::call() (avoids a sub-process from a web request). */
    private function migrationStatus(): array
    {
        try {
            $files = collect(File::files(database_path('migrations')))
                ->map(fn ($f) => str_replace('.php', '', $f->getFilename()))
                ->values();

            $ran = DB::table('migrations')->pluck('migration');

            $pending = $files->diff($ran)->count();

            return [
                'total'   => $files->count(),
                'ran'     => $ran->count(),
                'pending' => $pending,
            ];
        } catch (\Throwable) {
            return ['total' => null, 'ran' => null, 'pending' => null];
        }
    }

    public function cache(): array
    {
        $driver = config('cache.default');
        $healthy = false;
        $latencyMs = null;

        try {
            $key = 'health.cache.probe';
            $start = microtime(true);
            Cache::put($key, '1', 10);
            $healthy = Cache::get($key) === '1';
            Cache::forget($key);
            $latencyMs = round((microtime(true) - $start) * 1000, 1);
        } catch (\Throwable) {
            $healthy = false;
        }

        return [
            'driver'    => $driver,
            'healthy'   => $healthy,
            'latencyMs' => $latencyMs,
        ];
    }

    public function queue(): array
    {
        $driver = config('queue.default');
        $pending = null;
        $failed  = null;

       
        if ($driver === 'database') {
            try {
                $pending = DB::table('jobs')->count();
            } catch (\Throwable) {
                $pending = null;
            }
        }

        try {
            $failed = DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            $failed = null;
        }

        return [
            'driver'  => $driver,
            'pending' => $pending,
            'failed'  => $failed,
        ];
    }

   
    public function scheduler(): array
    {
        return [
            'configured'   => false,
            'lastRun'      => null,
            'nextRun'      => null,
        ];
    }

    public function storage(): array
    {
        return Cache::remember('health.storage', self::CACHE_TTL, function () {
            $usedMb = null;
            try {
                $disk = \Illuminate\Support\Facades\Storage::disk('public');
                $bytes = collect($disk->allFiles())->sum(fn ($file) => $disk->size($file));
                $usedMb = round($bytes / 1024 / 1024, 2);
            } catch (\Throwable) {
                $usedMb = null;
            }

            $availableMb = null;
            try {
                $free = @disk_free_space(storage_path());
                $availableMb = $free !== false ? round($free / 1024 / 1024, 2) : null;
            } catch (\Throwable) {
                $availableMb = null;
            }

            return ['usedMb' => $usedMb, 'availableMb' => $availableMb];
        });
    }

    
    public function backups(): array
    {
        return [
            'configured' => false,
            'latest'     => null,
            'status'     => null,
        ];
    }

    /** Last few ERROR-level lines from the Laravel log — bounded tail read, never the whole file. */
    public function recentErrors(int $limit = 5): array
    {
        $path = storage_path('logs/laravel.log');
        if (! is_file($path)) {
            return [];
        }

        try {
            $size = filesize($path);
            $chunk = min($size, 131072); // last 128KB is plenty for a handful of recent lines
            $handle = fopen($path, 'r');
            fseek($handle, -$chunk, SEEK_END);
            $tail = fread($handle, $chunk);
            fclose($handle);

            $lines = array_reverse(explode("\n", $tail));
            $cutoff = now()->subDay();
            $errors = [];
            foreach ($lines as $line) {
                if (preg_match('/^\[([\d\-: ]+)\].*?\.ERROR: (.+)$/', $line, $m)) {
                   
                    try {
                        if (\Carbon\Carbon::parse($m[1])->lt($cutoff)) {
                            continue;
                        }
                    } catch (\Throwable) {
                        continue;
                    }
                    $errors[] = ['time' => $m[1], 'message' => \Illuminate\Support\Str::limit($m[2], 160)];
                    if (count($errors) >= $limit) {
                        break;
                    }
                }
            }

            return $errors;
        } catch (\Throwable) {
            return [];
        }
    }

    public function systemAlerts(array $health): array
    {
        $alerts = [];

        if (! $health['database']['connected']) {
            $alerts[] = ['level' => 'danger', 'message' => 'Database connection is down.', 'source' => 'check'];
        }
        if (($health['database']['migrations']['pending'] ?? 0) > 0) {
            $alerts[] = ['level' => 'warning', 'message' => $health['database']['migrations']['pending'] . ' migration(s) pending.', 'source' => 'check'];
        }
        if (! $health['cache']['healthy']) {
            $alerts[] = ['level' => 'warning', 'message' => 'Cache store is not responding.', 'source' => 'check'];
        }
        if (($health['queue']['failed'] ?? 0) > 0) {
            $alerts[] = ['level' => 'warning', 'message' => $health['queue']['failed'] . ' failed queue job(s) need attention.', 'source' => 'check'];
        }
        if ($health['application']['debugMode'] && $health['application']['environment'] === 'production') {
            $alerts[] = ['level' => 'danger', 'message' => 'Debug mode is enabled in production.', 'source' => 'check'];
        }
        if (count($health['recentErrors'] ?? []) > 0) {
            $alerts[] = ['level' => 'info', 'message' => count($health['recentErrors']) . ' recent application error(s) logged.', 'source' => 'log'];
        }

        return $alerts;
    }

    public function all(): array
    {
        $health = [
            'application' => $this->application(),
            'database'    => $this->database(),
            'cache'       => $this->cache(),
            'queue'       => $this->queue(),
            'scheduler'   => $this->scheduler(),
            'storage'     => $this->storage(),
            'backups'     => $this->backups(),
            'recentErrors' => $this->recentErrors(),
        ];

        $health['alerts'] = $this->systemAlerts($health);

        return $health;
    }
}
