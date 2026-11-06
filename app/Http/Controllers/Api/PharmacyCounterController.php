<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Models\DrugDispensing;
use App\Models\DrugDispensingItem;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The pharmacy counter: a walk-in sale, and medicine brought back.
 *
 * Dispensing has always meant "against a prescription". Two other
 * things happen at the same counter and moved no stock in the system:
 * somebody buys paracetamol without seeing a doctor, and somebody
 * returns what they did not use.
 *
 * Both are dispensing rows with a different sale_type, so stock,
 * takings and history stay in one place instead of three.
 */
class PharmacyCounterController extends Controller
{
    use AuthorizesRoles;

    private function authCounter(Request $request)
    {
        return $this->authRoles($request, ['pharmacist'], 'This account cannot work the pharmacy counter.');
    }

    /**
     * Today's counter: what was sold over it and what came back.
     */
    public function index(Request $request)
    {
        $this->authCounter($request);

        $rows = DrugDispensing::with(['patient:id,name,mrn', 'items'])
            ->whereIn('sale_type', ['otc', 'return'])
            ->when($request->get('type'), fn ($q, $type) => $q->where('sale_type', $type))
            ->latest('id')
            ->limit(100)
            ->get();

        $today = $rows->filter(fn ($r) => (string) $r->dispensed_date === now()->toDateString());

        return response()->json([
            'stats' => [
                'sales_today' => $today->where('sale_type', 'otc')->count(),
                'takings_today' => round((float) $today->where('sale_type', 'otc')->sum('total_amount'), 2),
                'returns_today' => $today->where('sale_type', 'return')->count(),
                'refunded_today' => round((float) $today->where('sale_type', 'return')->sum('total_amount'), 2),
            ],
            'entries' => $rows->map(fn (DrugDispensing $d) => [
                'id' => $d->id,
                'dispensing_no' => $d->dispensing_no,
                'sale_type' => $d->sale_type,
                'patient_name' => $d->patient?->name,
                'patient_mrn' => $d->patient?->mrn,
                'date' => (string) $d->dispensed_date,
                'total_amount' => (float) $d->total_amount,
                'status' => $d->status,
                'notes' => $d->notes,
                'items' => $d->items->map(fn ($i) => [
                    'medicine_name' => $i->medicine_name,
                    'quantity' => (int) $i->quantity_dispensed,
                    'unit_price' => (float) $i->unit_price,
                    'total_price' => (float) $i->total_price,
                ])->all(),
            ])->all(),
        ]);
    }

    /**
     * A counter sale.
     *
     * It still needs a patient, because the table requires one and
     * because an over-the-counter antibiotic with nobody's name on it
     * is not something a hospital should be able to record.
     */
    public function sell(Request $request)
    {
        $user = $this->authCounter($request);

        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.drug_id' => ['required', 'exists:drugs,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);

        $dispensing = DB::transaction(function () use ($validated, $patient, $user) {
            $dispensing = DrugDispensing::create([
                'patient_id' => $patient->id,
                'prescription_id' => null,
                'sale_type' => 'otc',
                'dispensed_by' => $user->id,
                'dispensed_date' => now()->toDateString(),
                'total_amount' => 0,
                'status' => 'dispensed',
                'notes' => $validated['notes'] ?? null,
            ]);

            $total = 0;
            foreach ($validated['items'] as $line) {
                $drug = Drug::findOrFail($line['drug_id']);
                $price = (float) ($drug->unit_price ?? 0);
                $lineTotal = round($price * (int) $line['quantity'], 2);
                $total += $lineTotal;

                DrugDispensingItem::create([
                    'dispensing_id' => $dispensing->id,
                    'drug_id' => $drug->id,
                    'medicine_name' => $drug->drug_name,
                    'quantity_prescribed' => $line['quantity'],
                    'quantity_dispensed' => $line['quantity'],
                    'unit_price' => $price,
                    'total_price' => $lineTotal,
                ]);
            }

            $dispensing->update(['total_amount' => round($total, 2)]);

            return $dispensing->fresh();
        });

        return response()->json([
            'message' => "Sale {$dispensing->dispensing_no} recorded.",
            'id' => $dispensing->id,
            'total_amount' => (float) $dispensing->total_amount,
        ], 201);
    }

    /**
     * Medicine brought back, against what it was given out on.
     */
    public function returnItems(Request $request, DrugDispensing $dispensing)
    {
        $user = $this->authCounter($request);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_if($dispensing->sale_type === 'return', 422, 'That entry is itself a return.');

        $original = $dispensing->items->keyBy('id');

        $credit = DB::transaction(function () use ($validated, $dispensing, $original, $user) {
            $credit = DrugDispensing::create([
                'patient_id' => $dispensing->patient_id,
                'prescription_id' => $dispensing->prescription_id,
                'sale_type' => 'return',
                'returned_from_id' => $dispensing->id,
                'dispensed_by' => $user->id,
                'dispensed_date' => now()->toDateString(),
                'total_amount' => 0,
                'status' => 'dispensed',
                'notes' => $validated['reason'] ?? null,
            ]);

            $total = 0;
            foreach ($validated['items'] as $line) {
                $item = $original->get($line['item_id']);
                abort_if($item === null, 422, 'One of those lines is not on the original.');

                // More cannot come back than went out.
                abort_if(
                    (int) $line['quantity'] > (int) $item->quantity_dispensed,
                    422,
                    "Only {$item->quantity_dispensed} of {$item->medicine_name} were given out."
                );

                $lineTotal = round((float) $item->unit_price * (int) $line['quantity'], 2);
                $total += $lineTotal;

                DrugDispensingItem::create([
                    'dispensing_id' => $credit->id,
                    'drug_id' => $item->drug_id,
                    'medicine_name' => $item->medicine_name,
                    'quantity_prescribed' => $line['quantity'],
                    'quantity_dispensed' => $line['quantity'],
                    'unit_price' => $item->unit_price,
                    'total_price' => $lineTotal,
                ]);
            }

            $credit->update(['total_amount' => round($total, 2)]);

            return $credit->fresh();
        });

        return response()->json([
            'message' => 'Return recorded.',
            'id' => $credit->id,
            'refund_amount' => (float) $credit->total_amount,
        ], 201);
    }

    /**
     * Recent dispensings a return could be made against.
     */
    public function returnable(Request $request)
    {
        $this->authCounter($request);

        $rows = DrugDispensing::with(['patient:id,name,mrn', 'items'])
            ->where('sale_type', '!=', 'return')
            ->when($request->get('patient_id'), fn ($q, $id) => $q->where('patient_id', $id))
            ->latest('id')
            ->limit(50)
            ->get();

        return response()->json([
            'dispensings' => $rows->map(fn (DrugDispensing $d) => [
                'id' => $d->id,
                'dispensing_no' => $d->dispensing_no,
                'sale_type' => $d->sale_type,
                'patient_name' => $d->patient?->name,
                'date' => (string) $d->dispensed_date,
                'total_amount' => (float) $d->total_amount,
                'items' => $d->items->map(fn ($i) => [
                    'item_id' => $i->id,
                    'medicine_name' => $i->medicine_name,
                    'quantity' => (int) $i->quantity_dispensed,
                    'unit_price' => (float) $i->unit_price,
                ])->all(),
            ])->all(),
        ]);
    }
}
