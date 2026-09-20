<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function view(User $user, Patient $patient): bool
    {
        if ($user->hasRole('patient')) {
            return Patient::where('user_id', $user->id)->value('id') === $patient->id;
        }

        if ($user->hasRole('doctor')) {
            $doctor = Doctor::where('email', $user->email)->first();

            return $doctor && Appointment::where('patient_id', $patient->id)
                ->where('doctor_id', $doctor->id)
                ->exists();
        }

        // admin, super_admin, receptionist, nurse — full access.
        return $user->hasRole(['super_admin', 'admin', 'receptionist', 'nurse']);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $this->view($user, $patient);
    }
}
