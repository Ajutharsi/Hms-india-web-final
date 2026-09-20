<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Hospital;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\FailedJobThresholdExceeded;
use App\Notifications\HospitalTrialEnding;
use App\Notifications\NewSupportTicketCreated;
use App\Notifications\SubscriptionExpiringSoon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;


class NotificationCentreService
{
    private const MODULE = 'notifications';

    /** Matches DashboardService::platformNotifications()'s "soon" window. */
    private const SUBSCRIPTION_WINDOW_DAYS = 30;

    /** Matches DashboardService::platformNotifications()'s trial-ending window. */
    private const TRIAL_WINDOW_DAYS = 7;

    
    public const FAILED_JOB_THRESHOLD = 5;

    /** How long a failed-job-threshold notification suppresses a repeat. */
    private const FAILED_JOB_REPEAT_SUPPRESS_HOURS = 24;

    // ==================== Scheduled condition checks ====================

    public function runScheduledChecks(): array
    {
        return [
            'subscription_expiring' => $this->checkSubscriptionsExpiring(),
            'trial_ending' => $this->checkTrialsEnding(),
            'failed_job_threshold' => $this->checkFailedJobThreshold(),
        ];
    }

    public function checkSubscriptionsExpiring(): int
    {
        $hospitals = Hospital::query()
            ->whereNotNull('subscription_ends_at')
            ->whereBetween('subscription_ends_at', [now()->startOfDay(), now()->addDays(self::SUBSCRIPTION_WINDOW_DAYS)->endOfDay()])
            ->get();

        $sent = 0;
        foreach ($hospitals as $hospital) {
            $endsAt = $hospital->subscription_ends_at->format('Y-m-d');

            if ($this->alreadyNotified(SubscriptionExpiringSoon::class, fn ($data) => ($data['hospital_id'] ?? null) === $hospital->id && ($data['ends_at'] ?? null) === $endsAt)) {
                continue;
            }

            Notification::send($this->superAdmins(), new SubscriptionExpiringSoon($hospital));
            ActivityLog::log('notification_sent', self::MODULE, "Subscription-expiring notification sent for {$hospital->name} (ends {$endsAt})");
            $sent++;
        }

        return $sent;
    }

    public function checkTrialsEnding(): int
    {
        $hospitals = Hospital::query()
            ->where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now()->startOfDay(), now()->addDays(self::TRIAL_WINDOW_DAYS)->endOfDay()])
            ->get();

        $sent = 0;
        foreach ($hospitals as $hospital) {
            $endsAt = $hospital->trial_ends_at->format('Y-m-d');

            if ($this->alreadyNotified(HospitalTrialEnding::class, fn ($data) => ($data['hospital_id'] ?? null) === $hospital->id && ($data['trial_ends_at'] ?? null) === $endsAt)) {
                continue;
            }

            Notification::send($this->superAdmins(), new HospitalTrialEnding($hospital));
            ActivityLog::log('notification_sent', self::MODULE, "Trial-ending notification sent for {$hospital->name} (ends {$endsAt})");
            $sent++;
        }

        return $sent;
    }

    public function checkFailedJobThreshold(): int
    {
        $count = DB::table('failed_jobs')->count();

        if ($count <= self::FAILED_JOB_THRESHOLD) {
            return 0;
        }

        $recentlyNotified = DatabaseNotification::where('type', FailedJobThresholdExceeded::class)
            ->where('created_at', '>=', now()->subHours(self::FAILED_JOB_REPEAT_SUPPRESS_HOURS))
            ->exists();

        if ($recentlyNotified) {
            return 0;
        }

        Notification::send($this->superAdmins(), new FailedJobThresholdExceeded($count));
        ActivityLog::log('notification_sent', self::MODULE, "Failed-job-threshold notification sent ({$count} failed jobs)");

        return 1;
    }

    
    public function notifyNewSupportTicket(SupportTicket $ticket): void
    {
        Notification::send($this->superAdmins(), new NewSupportTicketCreated($ticket));
        ActivityLog::log('notification_sent', self::MODULE, "New-ticket notification sent for {$ticket->ticket_number}");
    }

   
    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function recentForBell(User $user, int $limit = 8): array
    {
        return $user->notifications()->latest()->limit($limit)->get()->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->data['title'] ?? 'Notification',
            'message' => $n->data['message'] ?? '',
            'level' => $n->data['level'] ?? 'info',
            'url' => $n->data['url'] ?? null,
            'read' => $n->read_at !== null,
            'created_at' => $n->created_at->diffForHumans(),
        ])->all();
    }

    /**
     * @return array{recordsTotal:int,recordsFiltered:int,data:array}
     */
    public function query(User $user, array $filters): array
    {
        $query = $user->notifications()->getQuery();
        $recordsTotal = (clone $query)->count();

        if (! empty($filters['status']) && $filters['status'] === 'unread') {
            $query->whereNull('read_at');
        } elseif (! empty($filters['status']) && $filters['status'] === 'read') {
            $query->whereNotNull('read_at');
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (! empty($filters['search'])) {
          
            $query->where('data', 'like', '%' . $filters['search'] . '%');
        }

        $recordsFiltered = (clone $query)->count();

        $rows = $query->latest()->when(isset($filters['length']) && (int) $filters['length'] > 0, fn ($q) => $q->skip((int) ($filters['start'] ?? 0))->take((int) $filters['length']))->get();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(fn ($n) => [
                'id' => $n->id,
                'type' => class_basename($n->type),
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'level' => $n->data['level'] ?? 'info',
                'url' => $n->data['url'] ?? null,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at->format('Y-m-d H:i:s'),
            ])->all(),
        ];
    }

    public function markRead(User $user, string $id): bool
    {
        $notification = $user->notifications()->find($id);
        if (! $notification) {
            return false;
        }
        $notification->markAsRead();

        return true;
    }

    public function markAllRead(User $user): int
    {
        $count = $user->unreadNotifications()->count();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return $count;
    }

    public function delete(User $user, string $id): bool
    {
        $notification = $user->notifications()->find($id);
        if (! $notification) {
            return false;
        }
        $notification->delete();

        return true;
    }

   
    private function superAdmins()
    {
        return User::role('super_admin')->get();
    }

    private function alreadyNotified(string $notificationClass, \Closure $matches): bool
    {
        return DatabaseNotification::where('type', $notificationClass)
            ->where('created_at', '>=', now()->subDays(self::SUBSCRIPTION_WINDOW_DAYS + 5))
            ->get()
            ->contains(fn ($n) => $matches($n->data));
    }
}
