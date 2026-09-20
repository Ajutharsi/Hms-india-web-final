<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BillingService;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
   
    public function create(array $data, int $userId): Invoice
    {
        return DB::transaction(function () use ($data, $userId) {
            $lines    = $this->buildLines($data['items']);
            $subtotal = collect($lines)->sum('total');

            $discount = (float) ($data['discount'] ?? 0);
            $tax      = (float) ($data['tax'] ?? 0);
            $total    = max(0, $subtotal - $discount + $tax);

            $invoice = Invoice::create([
                'patient_id'     => $data['patient_id'],
                'appointment_id' => $data['appointment_id'] ?? null,
                'invoice_date'   => now()->toDateString(),
                'due_date'       => $data['due_date'] ?? now()->addDays(30)->toDateString(),
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'tax'            => $tax,
                'total'          => $total,
                'paid_amount'    => 0,
                'balance'        => $total,
                'status'         => 'sent',
                'notes'          => $data['notes'] ?? null,
                'created_by'     => $userId,
            ]);

            $invoice->items()->createMany($lines);

            ActivityLog::log('created', 'Invoice', 'Invoice ' . $invoice->invoice_no . ' created', $invoice);

            return $invoice;
        });
    }

    
    public function addPayment(Invoice $invoice, array $data, int $userId): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $userId) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            $payment = Payment::create([
                'invoice_id'     => $invoice->id,
                'patient_id'     => $invoice->patient_id,
                'amount'         => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_date'   => now()->toDateString(),
                'reference_no'   => $data['reference_no'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'received_by'    => $userId,
            ]);

            $invoice->recalculate();

            ActivityLog::log('created', 'Payment', 'Payment ' . $payment->payment_no . ' recorded for ' . $invoice->invoice_no);

            return $payment;
        });
    }

    /**
     * @return array<int, array<string, mixed>> invoice item rows
     */
    private function buildLines(array $items): array
    {
        $serviceIds = collect($items)->pluck('billing_service_id')->filter()->unique();
        $services   = BillingService::whereIn('id', $serviceIds)->get()->keyBy('id');

        return collect($items)->map(function (array $item) use ($services) {
            $service = isset($item['billing_service_id'])
                ? $services->get($item['billing_service_id'])
                : null;

            // Server-side price when the item maps to a billing service.
            $unitPrice = $service ? (float) $service->price : (float) ($item['unit_price'] ?? 0);
            $quantity  = (int) $item['quantity'];
            $discount  = (float) ($item['discount'] ?? 0);

            return [
                'item_name'   => $service->service_name ?? $item['item_name'],
                'item_type'   => $item['item_type'] ?? ($service->category ?? 'Service'),
                'description' => $item['description'] ?? null,
                'quantity'    => $quantity,
                'unit_price'  => $unitPrice,
                'discount'    => $discount,
                'total'       => max(0, ($unitPrice * $quantity) - $discount),
            ];
        })->all();
    }
}
