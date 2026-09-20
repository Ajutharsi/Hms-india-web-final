<?php

namespace App\Http\Requests\Billing;

use App\Http\Requests\BaseFormRequest;

class AddPaymentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create billing');
    }

    public function rules(): array
    {
        return [
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:255',
            'reference_no'   => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:1000',
        ];
    }
}
