<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Delete a user while clearing every foreign key reference to users.id
     * across the schema, so the delete never hits an FK constraint violation.
     *
     * Nullable FK columns are cleared to null (matching their DB-level
     * ON DELETE SET NULL rule). The one NOT NULL + RESTRICT column
     * (patient_attachments.uploaded_by) is reassigned to a fallback
     * admin/super_admin user instead, so uploaded documents are never lost.
     * Rows tied to the user via ON DELETE CASCADE are removed explicitly too,
     * so behavior doesn't silently depend on the DB schema staying in sync.
     *
     * Everything runs in a single transaction: if any step fails, nothing
     * is left half-deleted.
     */
    protected function safelyDeleteUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $fallbackUserId = User::role(['super_admin', 'admin'])
                ->where('id', '!=', $user->id)
                ->orderBy('id')
                ->value('id') ?? 1;

            // NOT NULL + ON DELETE RESTRICT — must reassign, can't null out.
            DB::table('patient_attachments')
                ->where('uploaded_by', $user->id)
                ->update(['uploaded_by' => $fallbackUserId]);

            // Nullable FK columns — ON DELETE SET NULL at the DB level.
            DB::table('activity_logs')->where('user_id', $user->id)->update(['user_id' => null]);
            DB::table('consent_forms')->where('created_by', $user->id)->update(['created_by' => null]);
            DB::table('drug_dispensings')->where('dispensed_by', $user->id)->update(['dispensed_by' => null]);
            DB::table('drug_stocks')->where('performed_by', $user->id)->update(['performed_by' => null]);
            DB::table('fluid_logs')->where('logged_by', $user->id)->update(['logged_by' => null]);
            DB::table('food_logs')->where('logged_by', $user->id)->update(['logged_by' => null]);
            DB::table('invoices')->where('created_by', $user->id)->update(['created_by' => null]);
            DB::table('lab_results')->where('performed_by', $user->id)->update(['performed_by' => null]);
            DB::table('patients')->where('user_id', $user->id)->update(['user_id' => null]);
            DB::table('payments')->where('received_by', $user->id)->update(['received_by' => null]);
            DB::table('shift_handovers')->where('outgoing_nurse_id', $user->id)->update(['outgoing_nurse_id' => null]);
            DB::table('shift_handovers')->where('incoming_nurse_id', $user->id)->update(['incoming_nurse_id' => null]);

            // ON DELETE CASCADE columns — remove explicitly rather than relying on the DB.
            DB::table('round_assignments')
                ->where('assigned_by', $user->id)
                ->orWhere('staff_id', $user->id)
                ->delete();
            DB::table('staff_rounds')->where('staff_id', $user->id)->delete();
            DB::table('passkeys')->where('user_id', $user->id)->delete();

            $user->delete();
        });
    }

    /**
     * Shared custom validation messages used across all form requests.
     * Merge module-specific overrides on top of this array when needed.
     */
    protected function validationMessages(array $overrides = []): array
    {
        return array_merge([
            'required'           => 'The :attribute field is required.',
            'string'             => 'The :attribute must be text.',
            'min'                => 'The :attribute must be at least :min characters.',
            'max'                => 'The :attribute must not exceed :max characters.',
            'email'              => 'Please enter a valid email address.',
            'unique'             => 'This :attribute is already taken.',
            'numeric'            => 'The :attribute must be a number.',
            'integer'            => 'The :attribute must be a whole number.',
            'date'               => 'Please enter a valid date.',
            'in'                 => 'The selected :attribute is invalid.',
            'regex'              => 'The :attribute format is invalid.',
            'before'             => 'The :attribute must be a date before :date.',
            'after_or_equal'     => 'The :attribute must be today or a future date.',
            'confirmed'          => 'The :attribute confirmation does not match.',
            'exists'             => 'The selected :attribute is invalid.',
            'mimes'              => 'The :attribute must be a file of type: :values.',
            'image'              => 'The :attribute must be an image.',
            'size'               => 'The :attribute must not exceed :max kilobytes.',

            'phone.regex'             => 'Phone number may only contain numbers, +, -, spaces and ().',
            'phone.max'               => 'Phone number must not exceed 15 digits.',
            'emergency_contact.regex' => 'Phone number may only contain numbers, +, -, spaces and ().',
            'emergency_contact.max'   => 'Phone number must not exceed 15 digits.',
            'email.email'             => 'Please enter a valid email address.',
            'email.unique'            => 'This email is already taken.',
            'password.min'            => 'Password must be at least 8 characters.',
            'password.confirmed'      => 'Password confirmation does not match.',
        ], $overrides);
    }
}