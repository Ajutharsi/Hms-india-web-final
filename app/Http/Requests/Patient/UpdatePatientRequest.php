<?php

namespace App\Http\Requests\Patient;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        // Ownership/role rules live in PatientPolicy@update.
        return $this->user()->can('update', $this->route('patient'));
    }

    public function rules(): array
    {
        $patient = $this->route('patient');

        return [
            'name'                   => 'required|string|min:2|max:255',
            'phone'                  => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'email'                  => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($patient?->user_id)],
            'dob'                    => 'nullable|date|before:today',
            'gender'                 => 'required|in:male,female,other',
            'blood_group'            => 'nullable|string|max:255',
            'address'                => 'nullable|string|max:500',
            'emergency_contact'      => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'emergency_contact_name' => 'nullable|string|max:255',
            'nationality'            => 'nullable|string|max:255',
            'marital_status'         => 'nullable|in:single,married,divorced,widowed',
            'occupation'             => 'nullable|string|max:255',
            'patient_type'           => 'required|in:op,ip',
            'status'                 => 'required|in:active,discharged,deceased',
            'photo'                  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
}
