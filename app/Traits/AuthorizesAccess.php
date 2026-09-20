<?php

namespace App\Traits;

use App\Models\Doctor;
use App\Models\Patient;

trait AuthorizesAccess
{
    protected function isDoctorRole(): bool
    {
        return auth()->user()->hasRole('doctor');
    }

    protected function isPatientRole(): bool
    {
        return auth()->user()->hasRole('patient');
    }

    protected function getAuthDoctor(): ?Doctor
    {
        return Doctor::where('email', auth()->user()->email)->first();
    }

    protected function getAuthPatient(): ?Patient
    {
        return Patient::where('user_id', auth()->id())->first();
    }

    protected function abortIfNotOwner(bool $condition, string $message = 'Unauthorized'): void
    {
        if (! $condition) {
            abort(403, $message);
        }
    }
}
