<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Role checks for the mobile API.
 *
 * Every role-gated route on the web is written `role:super_admin|admin|…`:
 * an administrator sees the whole hospital, and the clinical roles see
 * their own corner of it. The mobile controllers had each spelled their
 * own `hasAnyRole([...])` without the two administrator roles, so an admin
 * signing in to the app was refused by every endpoint the web lets them
 * open. Saying it once here means the next guard cannot forget it.
 *
 * Two controllers deliberately do not use this. DoctorController's
 * endpoints answer "my appointments", "my patients" — questions that need
 * a Doctor record behind the account, which an administrator has not got.
 * PatientController is the same for a patient's own records. The admin
 * reaches that data through the hospital-wide lists instead.
 */
trait AuthorizesRoles
{
    /**
     * Passes when the signed-in user holds one of [$roles], or is an
     * administrator. Returns the user so guards can `return` it directly.
     */
    protected function authRoles(Request $request, array $roles, ?string $message = null): User
    {
        $allowed = [...$roles, 'admin', 'super_admin'];

        abort_unless(
            $request->user()->hasAnyRole($allowed),
            403,
            $message ?? 'This account cannot access this feature.',
        );

        return $request->user();
    }
}
