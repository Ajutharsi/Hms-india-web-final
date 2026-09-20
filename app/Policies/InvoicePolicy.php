<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->hasRole('patient')) {
            $patient = Patient::where('user_id', $user->id)->first();

            return (bool) ($patient && $invoice->patient_id === $patient->id);
        }

        // admin, super_admin, receptionist — full access.
        return $user->hasRole(['super_admin', 'admin', 'receptionist']);
    }

    public function create(User $user): bool
    {
        return $user->can('create billing');
    }

    public function addPayment(User $user, Invoice $invoice): bool
    {
        return $user->can('create billing') && $invoice->status !== 'cancelled';
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        // Deleting financial records is admin-only; paid invoices are immutable.
        return $user->hasRole(['super_admin', 'admin'])
            && (float) $invoice->paid_amount === 0.0;
    }
}
