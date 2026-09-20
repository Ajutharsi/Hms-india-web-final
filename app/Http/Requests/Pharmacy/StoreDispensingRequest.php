<?php

namespace App\Http\Requests\Pharmacy;

use App\Http\Requests\BaseFormRequest;

class StoreDispensingRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create pharmacy');
    }

    public function rules(): array
    {
        return [
            'patient_id'      => 'required|exists:patients,id',
            'prescription_id' => 'nullable|exists:prescriptions,id',
            'notes'           => 'nullable|string|max:1000',
            'items'           => 'required|array|min:1',
            'items.*.drug_id'             => 'required|exists:drugs,id',
            'items.*.quantity_dispensed'  => 'required|integer|min:1|max:10000',
            'items.*.quantity_prescribed' => 'nullable|integer|min:1|max:10000',
        ];
    }
}
