<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\DayEndClosing;
use App\Models\Expense;
use App\Models\IpdDeposit;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The books: what went out, and what was in the drawer at the end of
 * the day.
 *
 * Every rupee coming in was already recorded -- invoices, payments,
 * deposits -- and nothing going out, so nobody could answer "what did
 * we make today". Expenses are the other half.
 *
 * Day-end closing then counts both from the rows that exist and asks
 * for the one number a computer cannot know: the cash actually
 * counted. The gap between the two is the point.
 */
class AdminAccountsController extends Controller
{
    use AuthorizesRoles;

    private function authAdmin(Request $request)
    {
        return $this->authRoles($request, [], 'This account cannot see the books.');
    }

    // ---------------------------------------------------------- expenses

    public function expenses(Request $request)
    {
        $this->authAdmin($request);

        $month = $this->month($request->get('month'));
        [$start, $end] = $this->monthRange($month);

        $rows = Expense::with('recordedBy:id,name')
            ->whereBetween('expense_date', [$start, $end])
            ->latest('expense_date')
            ->limit(200)
            ->get();

        return response()->json([
            'month' => $month,
            'stats' => [
                'total' => round((float) $rows->sum('amount'), 2),
                'count' => $rows->count(),
                'cash' => round((float) $rows->where('payment_mode', 'cash')->sum('amount'), 2),
            ],
            // What the money went on, biggest first: the question an
            // administrator actually opens this screen with.
            'by_category' => $rows->groupBy('category')
                ->map(fn ($group, $category) => [
                    'category' => $category,
                    'total' => round((float) $group->sum('amount'), 2),
                    'count' => $group->count(),
                ])
                ->sortByDesc('total')
                ->values()
                ->all(),
            'expenses' => $rows->map(fn (Expense $e) => [
                'id' => $e->id,
                'date' => optional($e->expense_date)->format('Y-m-d'),
                'category' => $e->category,
                'paid_to' => $e->paid_to,
                'amount' => (float) $e->amount,
                'payment_mode' => $e->payment_mode,
                'reference_no' => $e->reference_no,
                'notes' => $e->notes,
                'recorded_by' => $e->recordedBy?->name,
            ])->all(),
        ]);
    }

    public function storeExpense(Request $request)
    {
        $user = $this->authAdmin($request);

        $validated = $request->validate([
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:60'],
            'paid_to' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => ['required', 'in:cash,card,upi,bank_transfer,cheque'],
            'reference_no' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $expense = Expense::create($validated + ['recorded_by' => $user->id]);

        return response()->json(['message' => 'Expense recorded.', 'id' => $expense->id], 201);
    }

    public function destroyExpense(Request $request, Expense $expense)
    {
        $this->authAdmin($request);

        // Once a day is closed its expenses are part of that closing.
        $closed = DayEndClosing::whereDate('closing_date', $expense->expense_date)->exists();
        abort_if($closed, 422, 'That day has been closed; the expense cannot be removed.');

        $expense->delete();

        return response()->json(['message' => 'Expense removed.']);
    }

    // ------------------------------------------------------- day-end

    /**
     * What a day looks like before it is closed: everything counted
     * from the rows, and the figure the person at the desk must supply.
     */
    public function dayEnd(Request $request)
    {
        $this->authAdmin($request);

        $date = $this->date($request->get('date'));
        $closing = DayEndClosing::whereDate('closing_date', $date)->first();

        $payments = Payment::whereDate('payment_date', $date)->get();
        $deposits = IpdDeposit::whereDate('deposited_at', $date)->get();
        $expenses = Expense::whereDate('expense_date', $date)->get();

        $cashIn = round(
            (float) $payments->where('payment_method', 'cash')->sum('amount')
            + (float) $deposits->where('payment_method', 'cash')->where('type', '!=', 'refund')->sum('amount'),
            2
        );
        $collections = round((float) $payments->sum('amount') + (float) $deposits->where('type', '!=', 'refund')->sum('amount'), 2);
        $spent = round((float) $expenses->sum('amount'), 2);
        $cashSpent = round((float) $expenses->where('payment_mode', 'cash')->sum('amount'), 2);

        // Yesterday's counted cash is today's opening, which is how a
        // drawer actually works.
        $opening = (float) (DayEndClosing::whereDate('closing_date', '<', $date)
            ->latest('closing_date')->first()->counted_cash ?? 0);

        return response()->json([
            'date' => $date,
            'closed' => $closing !== null,
            'counted' => [
                'collections' => $collections,
                'cash_collections' => $cashIn,
                'expenses' => $spent,
                'cash_expenses' => $cashSpent,
                'opening_cash' => $opening,
                'expected_cash' => round($opening + $cashIn - $cashSpent, 2),
                'payments' => $payments->count(),
                'deposits' => $deposits->count(),
            ],
            'closing' => $closing === null ? null : [
                'id' => $closing->id,
                'opening_cash' => (float) $closing->opening_cash,
                'collections' => (float) $closing->collections,
                'cash_collections' => (float) $closing->cash_collections,
                'expenses' => (float) $closing->expenses,
                'expected_cash' => (float) $closing->expected_cash,
                'counted_cash' => (float) $closing->counted_cash,
                'variance' => (float) $closing->variance,
                'variance_label' => $closing->variance_label,
                'notes' => $closing->notes,
                'closed_by' => $closing->closedBy?->name,
                'closed_at' => optional($closing->closed_at)->format('Y-m-d H:i'),
            ],
        ]);
    }

    public function closeDay(Request $request)
    {
        $user = $this->authAdmin($request);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();
        abort_if(
            DayEndClosing::whereDate('closing_date', $date)->exists(),
            422,
            'That day has already been closed.'
        );

        // Recomputed here rather than trusted from the app: the figures
        // on a closing must come from the rows, not from a screen.
        $payments = Payment::whereDate('payment_date', $date)->get();
        $deposits = IpdDeposit::whereDate('deposited_at', $date)->where('type', '!=', 'refund')->get();
        $expenses = Expense::whereDate('expense_date', $date)->get();

        $cashIn = round(
            (float) $payments->where('payment_method', 'cash')->sum('amount')
            + (float) $deposits->where('payment_method', 'cash')->sum('amount'),
            2
        );
        $collections = round((float) $payments->sum('amount') + (float) $deposits->sum('amount'), 2);
        $spent = round((float) $expenses->sum('amount'), 2);
        $cashSpent = round((float) $expenses->where('payment_mode', 'cash')->sum('amount'), 2);
        $opening = (float) (DayEndClosing::whereDate('closing_date', '<', $date)
            ->latest('closing_date')->first()->counted_cash ?? 0);

        $expected = round($opening + $cashIn - $cashSpent, 2);
        $counted = round((float) $validated['counted_cash'], 2);

        $closing = DayEndClosing::create([
            'closing_date' => $date,
            'opening_cash' => $opening,
            'collections' => $collections,
            'cash_collections' => $cashIn,
            'expenses' => $spent,
            'expected_cash' => $expected,
            'counted_cash' => $counted,
            'variance' => round($counted - $expected, 2),
            'notes' => $validated['notes'] ?? null,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        $gap = $closing->variance;
        $message = abs((float) $gap) < 0.01
            ? 'Day closed and balanced.'
            : 'Day closed ' . ((float) $gap > 0 ? 'over' : 'short') . ' by ' . number_format(abs((float) $gap), 2) . '.';

        return response()->json(['message' => $message, 'id' => $closing->id, 'variance' => (float) $gap], 201);
    }

    /** The last closings, so a run of short days is visible. */
    public function closings(Request $request)
    {
        $this->authAdmin($request);

        $rows = DayEndClosing::with('closedBy:id,name')->latest('closing_date')->limit(60)->get();

        return response()->json([
            'closings' => $rows->map(fn (DayEndClosing $c) => [
                'id' => $c->id,
                'date' => optional($c->closing_date)->format('Y-m-d'),
                'collections' => (float) $c->collections,
                'expenses' => (float) $c->expenses,
                'expected_cash' => (float) $c->expected_cash,
                'counted_cash' => (float) $c->counted_cash,
                'variance' => (float) $c->variance,
                'variance_label' => $c->variance_label,
                'closed_by' => $c->closedBy?->name,
                'closed_at' => optional($c->closed_at)->format('Y-m-d H:i'),
                'notes' => $c->notes,
            ])->all(),
        ]);
    }

    private function month(?string $value): string
    {
        return preg_match('/^\d{4}-\d{2}$/', (string) $value) ? $value : now()->format('Y-m');
    }

    private function monthRange(string $month): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();

        return [$start->toDateString(), (clone $start)->endOfMonth()->toDateString()];
    }

    private function date(?string $value): string
    {
        try {
            return $value ? Carbon::parse($value)->toDateString() : now()->toDateString();
        } catch (\Exception) {
            return now()->toDateString();
        }
    }
}
