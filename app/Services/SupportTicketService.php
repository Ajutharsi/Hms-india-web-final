<?php

namespace App\Services;

use App\Models\SupportTicket;
use Illuminate\Support\Facades\Cache;


class SupportTicketService
{
    private const CACHE_TTL = 60;

    public function kpis(): array
    {
        return Cache::remember('support.kpis', self::CACHE_TTL, function () {
            $byStatus = SupportTicket::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

            return [
                'total'            => (int) $byStatus->sum(),
                'open'             => (int) ($byStatus['open'] ?? 0),
                'inProgress'       => (int) ($byStatus['in_progress'] ?? 0),
                'waitingCustomer'  => (int) ($byStatus['waiting_customer'] ?? 0),
                'resolved'         => (int) ($byStatus['resolved'] ?? 0),
                'closed'           => (int) ($byStatus['closed'] ?? 0),
                'highPriority'     => SupportTicket::whereIn('status', ['open', 'assigned', 'in_progress', 'waiting_customer'])
                    ->where('priority', 'high')->count(),
                'critical'         => SupportTicket::whereIn('status', ['open', 'assigned', 'in_progress', 'waiting_customer'])
                    ->where('priority', 'critical')->count(),
                'overdue'          => SupportTicket::overdue()->count(),
                'avgResolutionHours' => SupportTicket::averageResolutionHours(),
            ];
        });
    }

    public function recentlyUpdated(int $limit = 8)
    {
        return SupportTicket::with(['hospital', 'assignedStaff'])
            ->latest('updated_at')->take($limit)->get();
    }

    
    public function notifications(): array
    {
        return Cache::remember('support.notifications', self::CACHE_TTL, function () {
            $notifications = [];

            $newCount = SupportTicket::where('status', 'open')
                ->where('created_at', '>=', now()->subDay())->count();
            if ($newCount > 0) {
                $notifications[] = $this->entry('info', 'tabler-ticket', "{$newCount} new support ticket(s) opened in the last 24 hours", ['status' => 'open']);
            }

            $assignedCount = SupportTicket::whereNotNull('assigned_to')
                ->where('updated_at', '>=', now()->subDay())
                ->whereIn('status', ['assigned', 'in_progress'])->count();
            if ($assignedCount > 0) {
                $notifications[] = $this->entry('info', 'tabler-user-check', "{$assignedCount} ticket(s) recently assigned", []);
            }

            $highCount = SupportTicket::whereIn('status', ['open', 'assigned', 'in_progress', 'waiting_customer'])
                ->where('priority', 'high')->count();
            if ($highCount > 0) {
                $notifications[] = $this->entry('warning', 'tabler-alert-triangle', "{$highCount} high priority ticket(s) need attention", ['priority' => 'high']);
            }

            $criticalCount = SupportTicket::whereIn('status', ['open', 'assigned', 'in_progress', 'waiting_customer'])
                ->where('priority', 'critical')->count();
            if ($criticalCount > 0) {
                $notifications[] = $this->entry('danger', 'tabler-alert-octagon', "{$criticalCount} critical ticket(s) need immediate attention", ['priority' => 'critical']);
            }

            $overdueCount = SupportTicket::overdue()->count();
            if ($overdueCount > 0) {
                $notifications[] = $this->entry('danger', 'tabler-clock-exclamation', "{$overdueCount} ticket(s) overdue", ['overdue' => 1]);
            }

            $closedCount = SupportTicket::where('status', 'closed')
                ->where('closed_at', '>=', now()->subDay())->count();
            if ($closedCount > 0) {
                $notifications[] = $this->entry('success', 'tabler-circle-check', "{$closedCount} ticket(s) closed in the last 24 hours", ['status' => 'closed']);
            }

            return $notifications;
        });
    }

    private function entry(string $level, string $icon, string $message, array $filters): array
    {
        return [
            'level'   => $level,
            'icon'    => $icon,
            'message' => $message,
            'url'     => route('super-admin.support.index', $filters),
            'source'  => 'check',
        ];
    }
}
