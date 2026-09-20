<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base class for all Form Requests.
 *
 * Centralizes the custom validation messages that were previously duplicated
 * via Controller::validationMessages() so every request class inherits them.
 * Subclasses can override messageOverrides() for module-specific wording.
 */
abstract class BaseFormRequest extends FormRequest
{
    public function messages(): array
    {
        return array_merge([
            'required'       => 'The :attribute field is required.',
            'string'         => 'The :attribute must be text.',
            'min'            => 'The :attribute must be at least :min characters.',
            'max'            => 'The :attribute must not exceed :max characters.',
            'email'          => 'Please enter a valid email address.',
            'unique'         => 'This :attribute is already taken.',
            'numeric'        => 'The :attribute must be a number.',
            'integer'        => 'The :attribute must be a whole number.',
            'date'           => 'Please enter a valid date.',
            'in'             => 'The selected :attribute is invalid.',
            'regex'          => 'The :attribute format is invalid.',
            'before'         => 'The :attribute must be a date before :date.',
            'after_or_equal' => 'The :attribute must be today or a future date.',
            'confirmed'      => 'The :attribute confirmation does not match.',
            'exists'         => 'The selected :attribute is invalid.',
            'mimes'          => 'The :attribute must be a file of type: :values.',
            'image'          => 'The :attribute must be an image.',
            'size'           => 'The :attribute must not exceed :max kilobytes.',

            'phone.regex'             => 'Phone number may only contain numbers, +, -, spaces and ().',
            'phone.max'               => 'Phone number must not exceed 15 digits.',
            'emergency_contact.regex' => 'Phone number may only contain numbers, +, -, spaces and ().',
            'emergency_contact.max'   => 'Phone number must not exceed 15 digits.',
            'email.email'             => 'Please enter a valid email address.',
            'email.unique'            => 'This email is already taken.',
            'password.min'            => 'Password must be at least 8 characters.',
            'password.confirmed'      => 'Password confirmation does not match.',
        ], $this->messageOverrides());
    }

    protected function messageOverrides(): array
    {
        return [];
    }
}
