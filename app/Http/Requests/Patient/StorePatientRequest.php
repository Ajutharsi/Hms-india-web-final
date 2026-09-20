<?php

namespace App\Http\Requests\Patient;

use App\Http\Requests\BaseFormRequest;

class StorePatientRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create patients');
    }

    public function rules(): array
    {
        return [
            'name'                   => 'required|string|min:2|max:255',
            'email'                  => 'nullable|email|max:255|unique:users,email',
            'phone'                  => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'dob'                    => 'nullable|date|before:today',
            'gender'                 => 'nullable|in:male,female,other',
            'blood_group'            => 'nullable|string|max:255',
            'address'                => 'nullable|string|max:500',
            'emergency_contact'      => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'emergency_contact_name' => 'nullable|string|max:255',
            'nationality'            => 'nullable|string|max:255',
            'marital_status'         => 'nullable|in:single,married,divorced,widowed',
            'occupation'             => 'nullable|string|max:255',
            'patient_type'           => 'required|in:op,ip',
            'status'                 => 'nullable|in:active,discharged,deceased',
            'photo'                  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
}
