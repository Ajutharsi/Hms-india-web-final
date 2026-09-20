<?php

namespace App\Http\Requests\Billing;

use App\Http\Requests\BaseFormRequest;

class StoreInvoiceRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create billing');
    }

    public function rules(): array
    {
        return [
            'patient_id'     => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'due_date'       => 'nullable|date',
            'notes'          => 'nullable|string|max:1000',
            'discount'       => 'nullable|numeric|min:0',
            'tax'            => 'nullable|numeric|min:0',
            'items'          => 'required|array|min:1',

            // billing_service_id is preferred: unit_price is then resolved
            // server-side from billing_services (see InvoiceService).
            'items.*.billing_service_id' => 'nullable|exists:billing_services,id',
            'items.*.item_name'          => 'required_without:items.*.billing_service_id|nullable|string|max:255',
            'items.*.item_type'          => 'nullable|string|max:100',
            'items.*.description'        => 'nullable|string|max:500',
            'items.*.unit_price'         => 'required_without:items.*.billing_service_id|nullable|numeric|min:0',
            'items.*.quantity'           => 'required|integer|min:1|max:1000',
            'items.*.discount'           => 'nullable|numeric|min:0',
        ];
    }
}
