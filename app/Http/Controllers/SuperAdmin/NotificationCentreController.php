<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\NotificationCentreService;
use Illuminate\Http\Request;

/**
 * Super Admin Notification Centre — see SUPER_ADMIN_NOTIFICATION_CENTRE_REPORT.md.
 * Every action here operates only on `auth()->user()->notifications()` —
 * Laravel's `Notifiable` relation is already scoped to the authenticated
 * user, so one super admin can never read, mark, or delete another's
 * notification row.
 */
class NotificationCentreController extends Controller
{
    public function __construct(private readonly NotificationCentreService $notifications)
    {
    }

    public function index()
    {
        return view('super-admin.notifications.index', [
            'unreadCount' => $this->notifications->unreadCount(auth()->user()),
        ]);
    }

    public function data(Request $request)
    {
        return response()->json($this->notifications->query(auth()->user(), $request->only(['status', 'type', 'from_date', 'to_date', 'search', 'length', 'start'])));
    }

    public function bell()
    {
        return response()->json([
            'unread_count' => $this->notifications->unreadCount(auth()->user()),
            'recent' => $this->notifications->recentForBell(auth()->user()),
        ]);
    }

    public function markRead(string $id)
    {
        abort_unless($this->notifications->markRead(auth()->user(), $id), 404);

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        $count = $this->notifications->markAllRead(auth()->user());

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function destroy(string $id)
    {
        abort_unless($this->notifications->delete(auth()->user(), $id), 404);

        return response()->json(['success' => true]);
    }
}
