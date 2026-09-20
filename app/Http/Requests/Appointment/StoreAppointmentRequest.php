<?php

namespace App\Http\Requests\Appointment;

use App\Http\Requests\BaseFormRequest;

class StoreAppointmentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        // Patients may only book for their own patient record.
        if ($user->hasRole('patient')) {
            $own = \App\Models\Patient::where('user_id', $user->id)->value('id');

            return $own && (int) $this->input('patient_id') === (int) $own;
        }

        // Doctors book for their patients; staff need the explicit permission.
        return $user->hasRole('doctor') || $user->can('create appointments');
    }

    public function rules(): array
    {
        return [
            'patient_id'       => 'required|exists:patients,id',
            'doctor_id'        => 'required|exists:doctors,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'type'             => 'required|in:op,emergency,consult,followup',
            'status'           => 'required|in:scheduled,completed,cancelled,no_show',
            'notes'            => 'nullable|string|max:1000',
        ];
    }

    // Data audit found 5 existing double-booked doctor/date/time slots with no
    // constraint stopping them — this prevents new ones without touching the
    // existing rows (ambiguous which side of each existing pair is "correct").
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
            ->exists();
    }
}
