<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\DutyRoster;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Who is on which shift.
 *
 * Attendance records what happened and leave records who is away; the
 * roster is what was planned, and it was the only one of the three
 * that lived on paper.
 *
 * A week at a time, because that is how a roster is read and argued
 * about. Approved leave is folded into the same answer -- rostering
 * somebody who is on leave is the mistake this screen exists to stop.
 */
class AdminRosterController extends Controller
{
    use AuthorizesRoles;

    private function authAdmin(Request $request)
    {
        return $this->authRoles($request, [], 'This account cannot manage the roster.');
    }

    public function index(Request $request)
    {
        $this->authAdmin($request);

        $start = $this->weekStart($request->get('week'));
        $end = (clone $start)->addDays(6);

        $staff = User::query()
            ->whereHas('roles', fn ($q) => $q->whereNotIn('name', ['patient', 'super_admin']))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $rosters = DutyRoster::with('ward:id,name')
            ->whereBetween('duty_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('user_id');

        // Approved leave in the same week, so the app can grey out a
        // person who should not be given a shift at all.
        $leave = LeaveRequest::where('status', 'approved')
            ->where('from_date', '<=', $end->toDateString())
            ->where('to_date', '>=', $start->toDateString())
            ->get()
            ->groupBy('user_id');

        $days = collect(range(0, 6))->map(fn ($i) => (clone $start)->addDays($i)->toDateString());

        return response()->json([
            'week_start' => $start->toDateString(),
            'days' => $days->all(),
            'stats' => [
                'staff' => $staff->count(),
                'shifts' => DutyRoster::whereBetween('duty_date', [$start->toDateString(), $end->toDateString()])
                    ->where('shift', '!=', 'off')->count(),
                'on_leave' => $leave->count(),
            ],
            'staff' => $staff->map(function (User $user) use ($rosters, $leave, $days) {
                $mine = $rosters->get($user->id, collect())->keyBy(
                    fn ($r) => optional($r->duty_date)->toDateString() ?? (string) $r->duty_date
                );
                $away = $leave->get($user->id, collect());

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->getRoleNames()->first(),
                    'days' => $days->map(function ($date) use ($mine, $away) {
                        $row = $mine->get($date);
                        $onLeave = $away->contains(
                            fn ($l) => $date >= Carbon::parse($l->from_date)->toDateString()
                                && $date <= Carbon::parse($l->to_date)->toDateString()
                        );

                        return [
                            'date' => $date,
                            'id' => $row?->id,
                            'shift' => $row?->shift,
                            'ward_name' => $row?->ward?->name,
                            'notes' => $row?->notes,
                            'on_leave' => $onLeave,
                        ];
                    })->all(),
                ];
            })->all(),
        ]);
    }

    /**
     * Set one person's shift for one day. Sending it twice replaces the
     * shift rather than failing, which is what rostering actually is.
     */
    public function store(Request $request)
    {
        $admin = $this->authAdmin($request);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'duty_date' => ['required', 'date'],
            'shift' => ['required', 'in:morning,evening,night,off'],
            'ward_id' => ['nullable', 'exists:wards,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $onLeave = LeaveRequest::where('user_id', $validated['user_id'])
            ->where('status', 'approved')
            ->where('from_date', '<=', $validated['duty_date'])
            ->where('to_date', '>=', $validated['duty_date'])
            ->exists();

        // Refused rather than warned: an approved absence is a decision
        // already taken, and a shift on top of it is a rota that lies.
        abort_if(
            $onLeave && $validated['shift'] !== 'off',
            422,
            'That person has approved leave on this day.'
        );

        $roster = DutyRoster::updateOrCreate(
            ['user_id' => $validated['user_id'], 'duty_date' => $validated['duty_date']],
            [
                'shift' => $validated['shift'],
                'ward_id' => $validated['ward_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $admin->id,
            ]
        );

        return response()->json(['message' => 'Shift set.', 'id' => $roster->id], 201);
    }

    public function destroy(Request $request, DutyRoster $roster)
    {
        $this->authAdmin($request);
        $roster->delete();

        return response()->json(['message' => 'Shift cleared.']);
    }

    /** Monday of the week being shown. */
    private function weekStart(?string $week): Carbon
    {
        try {
            return $week ? Carbon::parse($week)->startOfWeek() : now()->startOfWeek();
        } catch (\Exception) {
            return now()->startOfWeek();
        }
    }
}
