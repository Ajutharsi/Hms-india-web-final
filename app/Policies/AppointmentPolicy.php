<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;

class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('patient')) {
            $patient = Patient::where('user_id', $user->id)->first();

            return (bool) ($patient && $appointment->patient_id === $patient->id);
        }

        if ($user->hasRole('doctor')) {
            $doctor = Doctor::where('email', $user->email)->first();

            return (bool) ($doctor && $appointment->doctor_id === $doctor->id);
        }

        // admin, super_admin, receptionist — full access.
        return $user->hasRole(['super_admin', 'admin', 'receptionist']);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }
}
