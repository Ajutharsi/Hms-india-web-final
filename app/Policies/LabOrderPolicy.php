<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\User;

class LabOrderPolicy
{
    public function view(User $user, LabOrder $labOrder): bool
    {
        if ($user->hasRole('patient')) {
            $patient = Patient::where('user_id', $user->id)->first();

            return (bool) ($patient && $labOrder->patient_id === $patient->id);
        }

        if ($user->hasRole('doctor')) {
            $doctor = Doctor::where('email', $user->email)->first();

            return (bool) ($doctor && $labOrder->doctor_id === $doctor->id);
        }

        // admin, super_admin, lab_assistant, receptionist — full access.
        return $user->hasRole(['super_admin', 'admin', 'lab_assistant', 'receptionist']);
    }
}
