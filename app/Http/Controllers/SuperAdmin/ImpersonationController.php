<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hospital;
use App\Models\Impersonation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * "Login as Hospital Admin" — lets a Super Admin securely enter a hospital
 * admin's session for troubleshooting/support, without ever destroying
 * their own original session. See SUPER_ADMIN_IMPERSONATION_REPORT.md for
 * the full security design.
 */
class ImpersonationController extends Controller
{
    /**
     * Create the admin account for a hospital that doesn't have one yet.
     * A deliberate, separate step from start() — creating an account and
     * impersonating it are two different actions with two different
     * blast radii, and conflating them made the confirmation UX unclear.
     */
    public function createAdmin(Request $request, Hospital $hospital)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ], $this->validationMessages());

        $admin = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'hospital_id' => $hospital->id,
        ]);
        $admin->assignRole('admin');

        ActivityLog::log('admin_created', 'impersonation', "Admin account created for {$hospital->name}: {$admin->name}", $admin);

        return redirect()->route('super-admin.hospitals.show', $hospital)
            ->with('success', "Admin account created for {$hospital->name}.");
    }

    /**
     * Start impersonating a hospital's admin. The target user is never
     * taken from client input — it's always resolved server-side from the
     * hospital's own linked admin() relation (role = 'admin' only), which
     * is what actually prevents privilege escalation via a tampered user
     * id: there is no user id parameter to tamper with in the first place.
     */
    public function start(Request $request, Hospital $hospital)
    {
        if (session('impersonator_id')) {
            return redirect()->back()
                ->with('error', 'End your current impersonation session before starting another one.');
        }

        $admin = $hospital->adminUser();

        if (! $admin) {
            return redirect()->back()
                ->with('error', "No admin account exists for {$hospital->name} yet. Create one first.");
        }

        // Defense in depth — adminUser() already filters to role 'admin' only,
        // so these should be unreachable, but a resolved target is re-checked
        // explicitly rather than trusted implicitly.
        if ($admin->hasRole('super_admin')) {
            abort(403, 'Cannot impersonate a Super Admin account.');
        }
        if ($admin->id === auth()->id()) {
            abort(403, 'Cannot impersonate yourself.');
        }

        $superAdmin = auth()->user();

        $impersonation = Impersonation::create([
            'super_admin_id'        => $superAdmin->id,
            'hospital_id'           => $hospital->id,
            'impersonated_user_id'  => $admin->id,
            'started_at'            => now(),
            'ip_address'            => $request->ip(),
            'user_agent'            => substr((string) $request->userAgent(), 0, 255),
        ]);

        // Logged while still authenticated as the super admin, so the audit
        // trail correctly attributes the "started" event to them, not the
        // account they're about to become.
        ActivityLog::log(
            'impersonation_started',
            'impersonation',
            "{$superAdmin->name} started impersonating {$admin->name} (Hospital: {$hospital->name})",
            $impersonation
        );

        session([
            'impersonator_id'  => $superAdmin->id,
            'impersonation_id' => $impersonation->id,
        ]);

        // Auth::login() swaps the authenticated user for the current session
        // without invalidating or regenerating it — the original session
        // (and the super admin's own identity, recoverable via
        // impersonator_id) is never destroyed.
        Auth::login($admin);

        return redirect()->route('dashboard')
            ->with('success', "You are now viewing {$hospital->name} as {$admin->name}.");
    }

    /**
     * Exit impersonation and return to the original Super Admin identity.
     * Deliberately NOT behind role:super_admin — the currently authenticated
     * user during an active impersonation session IS the hospital admin, not
     * a super_admin, so that middleware would lock them out of their own
     * exit route. Guarded instead by the impersonator_id session marker,
     * which only a real prior start() call can set.
     */
    public function stop(Request $request)
    {
        $impersonatorId = session('impersonator_id');
        $impersonationId = session('impersonation_id');

        if (! $impersonatorId) {
            return redirect()->route('dashboard');
        }

        $impersonatedUser = auth()->user();
        $originalUser = User::find($impersonatorId);
        $impersonation = Impersonation::find($impersonationId);

        if ($impersonation && ! $impersonation->ended_at) {
            $impersonation->update(['ended_at' => now()]);
        }

        session()->forget(['impersonator_id', 'impersonation_id']);

        if ($originalUser) {
            Auth::login($originalUser);

            ActivityLog::log(
                'impersonation_ended',
                'impersonation',
                "{$originalUser->name} ended impersonation of " . ($impersonatedUser?->name ?? 'a user') . ' (Hospital: ' . ($impersonation?->hospital?->name ?? 'unknown') . ')',
                $impersonation
            );

            return redirect()->route('dashboard')->with('success', 'Returned to your Super Admin account.');
        }

        // The original super admin account no longer exists — fail safe by
        // logging out completely rather than leaving the browser
        // authenticated as whatever was last in the session.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('error', 'Your original account could not be restored. Please log in again.');
    }
}
