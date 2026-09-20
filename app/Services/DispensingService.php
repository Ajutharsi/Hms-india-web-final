<?php

namespace App\Services;

use App\Models\Drug;
use App\Models\DrugDispensing;
use App\Models\DrugStock;
use App\Models\MedicationAdministration;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DispensingService
{
   
    public function dispense(array $data, int $userId): DrugDispensing
    {
        return DB::transaction(function () use ($data, $userId) {
            $drugIds = collect($data['items'])->pluck('drug_id')->unique();
            $drugs   = Drug::whereIn('id', $drugIds)->lockForUpdate()->get()->keyBy('id');

            $totalAmount = 0;
            $lines       = [];

            foreach ($data['items'] as $item) {
                $drug = $drugs->get($item['drug_id']);
                $qty  = (int) $item['quantity_dispensed'];

                if ($drug->current_stock < $qty) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$drug->drug_name}: {$drug->current_stock} available, {$qty} requested.",
                    ]);
                }

                $lineTotal    = $drug->unit_price * $qty;
                $totalAmount += $lineTotal;

                $lines[] = [
                    'drug_id'             => $drug->id,
                    'medicine_name'       => $drug->drug_name,
                    'quantity_prescribed' => $item['quantity_prescribed'] ?? $qty,
                    'quantity_dispensed'  => $qty,
                    'unit_price'          => $drug->unit_price,
                    'total_price'         => $lineTotal,
                ];
            }

            $dispensing = DrugDispensing::create([
                'patient_id'      => $data['patient_id'],
                'prescription_id' => $data['prescription_id'] ?? null,
                'dispensed_by'    => $userId,
                'dispensed_date'  => now()->toDateString(),
                'total_amount'    => $totalAmount,
                'status'          => 'dispensed',
                'notes'           => $data['notes'] ?? null,
            ]);

            $dispensing->items()->createMany($lines);

            $admissionId = Patient::find($data['patient_id'])?->currentAdmission?->id;
            foreach ($lines as $line) {
                MedicationAdministration::create([
                    'patient_id'      => $data['patient_id'],
                    'admission_id'    => $admissionId,
                    'nurse_id'        => $userId,
                    'drug_name'       => $line['medicine_name'],
                    'dosage'          => 'Qty: ' . $line['quantity_dispensed'],
                    'status'          => 'administered',
                    'administered_at' => now(),
                    'notes'           => 'Auto-recorded from pharmacy dispensing ' . $dispensing->dispensing_no,
                ]);
            }

            foreach ($lines as $line) {
                $drug       = $drugs->get($line['drug_id']);
                $newBalance = $drug->current_stock - $line['quantity_dispensed'];

                DrugStock::create([
                    'drug_id'      => $line['drug_id'],
                    'type'         => 'out',
                    'quantity'     => $line['quantity_dispensed'],
                    'balance'      => $newBalance,
                    'reason'       => 'Dispensed',
                    'reference'    => $dispensing->dispensing_no,
                    'performed_by' => $userId,
                ]);

                $drug->update(['current_stock' => $newBalance]);
            }

            return $dispensing;
        });
    }
}
