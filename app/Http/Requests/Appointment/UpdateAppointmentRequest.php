<?php

namespace App\Http\Requests\Appointment;

use App\Http\Requests\BaseFormRequest;

class UpdateAppointmentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('appointment'));
    }

    public function rules(): array
    {
        return [
            'patient_id'       => 'required|exists:patients,id',
            'doctor_id'        => 'required|exists:doctors,id',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required|date_format:H:i',
            'type'             => 'required|in:op,emergency,consult,followup',
            'status'           => 'required|in:scheduled,completed,cancelled,no_show',
            'notes'            => 'nullable|string|max:1000',
        ];
    }

    // Same conflict guard as StoreAppointmentRequest — excludes the appointment
    // being edited so re-saving it with unchanged date/time isn't flagged as a
    // conflict with itself.
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasConflictingAppointment()) {
                $validator->errors()->add(
                    'appointment_time',
                    'This doctor already has another appointment at this date and time.'
                );
            }
        });
    }

    private function hasConflictingAppointment(): bool
    {
        $doctorId = $this->input('doctor_id');
        $date = $this->input('appointment_date');
        $time = $this->input('appointment_time');

        if (!$doctorId || !$date || !$time || in_array($this->input('status'), ['cancelled', 'no_show'], true)) {
            return false;
        }

        return \App\Models\Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->where('appointment_time', $time)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where('id', '!=', $this->route('appointment')->id)
            ->exists();
    }
}
