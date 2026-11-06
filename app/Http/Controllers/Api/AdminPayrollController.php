<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Salary runs, a month at a time.
 *
 * Deliberately not clever: there is no tax engine and no slab table
 * here, because every hospital's are different and a wrong one is
 * worse than none. What this does is hold the three numbers somebody
 * works out -- basic, allowances, deductions -- keep the arithmetic
 * honest, and record when it was paid.
 *
 * The attendance figures are shown beside each line because that is
 * what the deduction usually argues with.
 */
class AdminPayrollController extends Controller
{
    use AuthorizesRoles;

    private function authAdmin(Request $request)
    {
        return $this->authRoles($request, [], 'This account cannot run payroll.');
    }

    public function index(Request $request)
    {
        $this->authAdmin($request);

        $period = $this->period($request->get('period'));
        [$start, $end] = $this->monthRange($period);

        $rows = Payroll::with('user:id,name,email')
            ->where('period', $period)
            ->get()
            ->keyBy('user_id');

        $staff = User::query()
            ->whereHas('roles', fn ($q) => $q->whereNotIn('name', ['patient', 'super_admin']))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $attendance = Attendance::selectRaw('user_id, status, COUNT(*) as days')
            ->whereBetween('attendance_date', [$start, $end])
            ->groupBy('user_id', 'status')
            ->get()
            ->groupBy('user_id');

        $lines = $staff->map(function (User $user) use ($rows, $attendance, $period) {
            $row = $rows->get($user->id);
            $counts = $attendance->get($user->id, collect())->pluck('days', 'status');

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'role' => $user->getRoleNames()->first(),
                'period' => $period,
                'payroll_id' => $row?->id,
                'basic' => (float) ($row->basic ?? 0),
                'allowances' => (float) ($row->allowances ?? 0),
                'deductions' => (float) ($row->deductions ?? 0),
                'net_pay' => (float) ($row->net_pay ?? 0),
                'status' => $row->status ?? 'not run',
                'paid_on' => optional($row?->paid_on)->format('Y-m-d'),
                'present_days' => (int) ($counts['present'] ?? 0),
                'absent_days' => (int) ($counts['absent'] ?? 0),
            ];
        });

        return response()->json([
            'period' => $period,
            'stats' => [
                'staff' => $lines->count(),
                'run' => $lines->where('status', '!=', 'not run')->count(),
                'paid' => $lines->where('status', 'paid')->count(),
                'total_net' => round($lines->sum('net_pay'), 2),
            ],
            'lines' => $lines->all(),
        ]);
    }

    public function store(Request $request)
    {
        $admin = $this->authAdmin($request);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'basic' => ['required', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $net = Payroll::netOf($validated);
        abort_if($net < 0, 422, 'Deductions are more than basic plus allowances.');

        $payroll = Payroll::updateOrCreate(
            ['user_id' => $validated['user_id'], 'period' => $validated['period']],
            [
                'basic' => $validated['basic'],
                'allowances' => $validated['allowances'] ?? 0,
                'deductions' => $validated['deductions'] ?? 0,
                // Never taken from the caller: it is the other three.
                'net_pay' => $net,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $admin->id,
            ]
        );

        return response()->json(['message' => 'Payroll line saved.', 'id' => $payroll->id], 201);
    }

    /** Mark one line paid. */
    public function pay(Request $request, Payroll $payroll)
    {
        $this->authAdmin($request);

        $validated = $request->validate([
            'payment_mode' => ['required', 'in:cash,bank_transfer,cheque,upi'],
            'paid_on' => ['nullable', 'date'],
        ]);

        abort_if($payroll->status === 'paid', 422, 'That line is already marked paid.');

        $payroll->update([
            'status' => 'paid',
            'payment_mode' => $validated['payment_mode'],
            'paid_on' => $validated['paid_on'] ?? now()->toDateString(),
        ]);

        return response()->json(['message' => 'Marked paid.']);
    }

    public function destroy(Request $request, Payroll $payroll)
    {
        $this->authAdmin($request);

        // A paid payslip is a record of money that moved.
        abort_if($payroll->status === 'paid', 422, 'A paid line cannot be removed.');

        $payroll->delete();

        return response()->json(['message' => 'Payroll line removed.']);
    }

    private function period(?string $value): string
    {
        return preg_match('/^\d{4}-\d{2}$/', (string) $value) ? $value : now()->format('Y-m');
    }

    private function monthRange(string $period): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfMonth();

        return [$start->toDateString(), (clone $start)->endOfMonth()->toDateString()];
    }
}
