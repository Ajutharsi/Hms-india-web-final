<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Doctor;
use App\Models\User;
use App\Services\SubscriptionLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The people an administrator manages: doctors, user accounts and staff.
 *
 * Two rules from the web are carried over deliberately rather than
 * reimplemented loosely, because getting either wrong here would give the
 * phone a way round a control the browser enforces:
 *
 *  - only a super admin may hand out the super admin role;
 *  - creating a doctor or a user counts against the hospital's plan, and
 *    SubscriptionLimitService decides whether there is room.
 *
 * File uploads — a doctor's photo, licence and certificates — are left to
 * the web. They are not something anyone does from a phone, and a
 * multipart upload path here would be a second place to get wrong.
 */
class AdminDirectoryController extends Controller
{
    use AuthorizesRoles;

    public function __construct(private readonly SubscriptionLimitService $limits)
    {
    }

    private function authAdmin(Request $request): User
    {
        return $this->authRoles($request, [], 'This account is not an administrator account.');
    }

    /** The web's own rule: granting super_admin takes a super_admin. */
    private function onlySuperAdminMayGrantSuperAdmin(Request $request): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($request) {
            if ($value === 'super_admin' && !$request->user()?->hasRole('super_admin')) {
                $fail('Only a Super Admin can assign the Super Admin role.');
            }
        };
    }

    /** Turns the service's exception into a 422 the app can show. */
    private function assertRoom(string $resource, User $actor): void
    {
        try {
            $this->limits->assertCanCreate($resource, $actor);
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }
    }

    // ------------------------------------------------------------- doctors

    public function doctors(Request $request)
    {
        $this->authAdmin($request);

        $doctors = Doctor::query()
            ->when($request->search, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$v}%")
                ->orWhere('doctor_id', 'like', "%{$v}%")
                ->orWhere('specialization', 'like', "%{$v}%")))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('name')
            ->limit(300)
            ->get();

        return response()->json([
            'usage' => $this->limits->resourceSummary('doctor'),
            'doctors' => $doctors->map(fn ($d) => $this->doctorPayload($d)),
        ]);
    }

    public function doctorDetail(Request $request, Doctor $doctor)
    {
        $this->authAdmin($request);

        return response()->json([
            'doctor' => $this->doctorPayload($doctor) + [
                'qualification' => $doctor->qualification,
                'registration_number' => $doctor->registration_number,
                'license_expiry' => $doctor->license_expiry,
            ],
            // The column is `day`, not `day_of_week` — worth naming the
            // fields rather than returning the row, so a rename shows up
            // here instead of silently changing the app's payload.
            'schedules' => $doctor->schedules()->orderBy('day')->get()->map(fn ($s) => [
                'id' => $s->id,
                'day' => $s->day,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'slot_duration' => $s->slot_duration,
                'is_available' => (bool) $s->is_available,
            ]),
        ]);
    }

    private function doctorPayload(Doctor $d): array
    {
        return [
            'id' => $d->id,
            'doctor_id' => $d->doctor_id,
            'name' => $d->name,
            'email' => $d->email,
            'phone' => $d->phone,
            'specialization' => $d->specialization,
            'department' => $d->department,
            'consultation_fee' => (float) $d->consultation_fee,
            'status' => $d->status,
        ];
    }

    private function doctorRules(?Doctor $doctor = null): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('doctors', 'email')->ignore($doctor?->id)],
            'phone' => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
            'specialization' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255', Rule::unique('doctors', 'registration_number')->ignore($doctor?->id)],
            'consultation_fee' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }

    public function doctorStore(Request $request)
    {
        $admin = $this->authAdmin($request);
        $validated = $request->validate($this->doctorRules());
        $this->assertRoom('doctor', $admin);

        $doctor = Doctor::create($validated + [
            'consultation_fee' => $validated['consultation_fee'] ?? 0,
            'status' => $validated['status'] ?? 'active',
        ]);

        ActivityLog::log('created', 'Doctors', "Doctor {$doctor->name} added from the mobile app");

        return response()->json(['message' => 'Doctor added.', 'id' => $doctor->id], 201);
    }

    public function doctorUpdate(Request $request, Doctor $doctor)
    {
        $this->authAdmin($request);
        $doctor->update($request->validate($this->doctorRules($doctor)));

        return response()->json(['message' => 'Doctor updated.']);
    }

    public function doctorDestroy(Request $request, Doctor $doctor)
    {
        $this->authAdmin($request);
        $name = $doctor->name;
        $doctor->delete();

        ActivityLog::log('deleted', 'Doctors', "Doctor {$name} removed from the mobile app");

        return response()->json(['message' => 'Doctor deleted.']);
    }

    // --------------------------------------------------------------- users

    public function users(Request $request)
    {
        $this->authAdmin($request);

        $users = User::with('roles')
            ->when($request->search, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$v}%")
                ->orWhere('email', 'like', "%{$v}%")))
            ->when($request->role, fn ($q, $v) => $q->whereHas('roles', fn ($r) => $r->where('name', $v)))
            ->orderBy('name')
            ->limit(300)
            ->get();

        return response()->json([
            'usage' => $this->limits->resourceSummary('user'),
            'roles' => \Spatie\Permission\Models\Role::orderBy('name')->pluck('name'),
            'users' => $users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->roles->first()?->name,
                'created_at' => $u->created_at?->toDateString(),
            ]),
        ]);
    }

    public function userStore(Request $request)
    {
        $admin = $this->authAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
            'role' => ['required', 'in:super_admin,admin,doctor,nurse,receptionist,lab_assistant,pharmacist,staff,patient',
                       $this->onlySuperAdminMayGrantSuperAdmin($request)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // A super admin is not a billable hospital seat; patients come out
        // of their own pool. Same split as the web.
        $resource = match ($validated['role']) {
            'super_admin' => null,
            'patient' => 'patient',
            default => 'user',
        };
        if ($resource !== null) {
            $this->assertRoom($resource, $admin);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);
        $user->assignRole($validated['role']);

        ActivityLog::log('created', 'Users', "User {$user->email} created from the mobile app");

        return response()->json(['message' => 'User created.', 'id' => $user->id], 201);
    }

    public function userUpdate(Request $request, User $user)
    {
        $this->authAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
            'role' => ['required', 'in:super_admin,admin,doctor,nurse,receptionist,lab_assistant,pharmacist,staff,patient',
                       $this->onlySuperAdminMayGrantSuperAdmin($request)],
        ]);

        $user->update(collect($validated)->except('role')->all());
        $user->syncRoles([$validated['role']]);

        return response()->json(['message' => 'User updated.']);
    }

    public function userResetPassword(Request $request, User $user)
    {
        $this->authAdmin($request);

        $validated = $request->validate(['password' => ['required', 'confirmed', Password::defaults()]]);
        $user->update(['password' => Hash::make($validated['password'])]);

        ActivityLog::log('updated', 'Users', "Password reset for {$user->email} from the mobile app");

        return response()->json(['message' => 'Password reset.']);
    }

    public function userDestroy(Request $request, User $user)
    {
        $admin = $this->authAdmin($request);

        abort_if($user->id === $admin->id, 422, 'You cannot delete your own account.');
        abort_if($user->hasRole('super_admin') && !$admin->hasRole('super_admin'), 403,
            'Only a Super Admin can remove a Super Admin account.');

        $email = $user->email;
        $user->delete();

        ActivityLog::log('deleted', 'Users', "User {$email} deleted from the mobile app");

        return response()->json(['message' => 'User deleted.']);
    }

    // --------------------------------------------------------------- staff

    /** Staff are User rows holding one of the non-clinical hospital roles. */
    public function staff(Request $request)
    {
        $this->authAdmin($request);

        $staff = User::with('roles')
            ->whereHas('roles', fn ($r) => $r->whereIn('name', ['staff', 'nurse', 'receptionist', 'lab_assistant', 'pharmacist']))
            ->when($request->search, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$v}%")
                ->orWhere('email', 'like', "%{$v}%")))
            ->when($request->role, fn ($q, $v) => $q->whereHas('roles', fn ($r) => $r->where('name', $v)))
            ->orderBy('name')
            ->limit(300)
            ->get();

        return response()->json([
            'by_role' => $staff->groupBy(fn ($u) => $u->roles->first()?->name)->map->count(),
            'staff' => $staff->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->roles->first()?->name,
            ]),
        ]);
    }
}
