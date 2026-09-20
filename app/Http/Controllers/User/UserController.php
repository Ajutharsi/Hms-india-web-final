<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SubscriptionLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function __construct(private readonly SubscriptionLimitService $limits)
    {
    }

    /**
     * Only an existing super_admin may grant the super_admin role to a user
     * (self or otherwise) — closes the privilege-escalation gap where a
     * plain admin (also allowed on these routes) could self-promote or
     * promote anyone else. See SUPER_ADMIN_MODULE_ANALYSIS.md §7/§14.
     */
    private function onlySuperAdminMayGrantSuperAdmin(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            if ($value === 'super_admin' && !auth()->user()?->hasRole('super_admin')) {
                $fail('Only a Super Admin can assign the Super Admin role.');
            }
        };
    }

    public function index()
    {
        return view('users.index', [
            'userUsage' => $this->limits->resourceSummary('user'),
        ]);
    }

    public function data()
    {
        $query = User::with('roles')->latest();

        return DataTables::of($query)
            ->addColumn('role', fn($u) => $u->roles->first()?->name ?? 'N/A')
            ->editColumn('phone', fn($u) => $u->phone ?? 'N/A')
            ->editColumn('created_at', fn($u) => $u->created_at->format('d M Y'))
            ->addColumn('actions', fn($u) => '
                <div class="d-flex gap-1">
                    <button onclick="editUser(' . $u->id . ')" class="btn btn-sm btn-icon btn-label-warning" title="Edit">
                        <i class="icon-base ti tabler-edit"></i>
                    </button>
                    <button onclick="resetPassword(' . $u->id . ')" class="btn btn-sm btn-icon btn-label-secondary" title="Reset Password">
                        <i class="icon-base ti tabler-lock"></i>
                    </button>
                    <button onclick="deleteUser(' . $u->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete">
                        <i class="icon-base ti tabler-trash"></i>
                    </button>
                </div>
            ')
            ->filterColumn('role', fn($q, $kw) => $q->whereHas('roles', fn($r) => $r->where('name', 'like', "%{$kw}%")))
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|min:2|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'phone'    => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'role'     => ['required', 'in:super_admin,admin,doctor,nurse,receptionist,lab_assistant,pharmacist,staff,patient', $this->onlySuperAdminMayGrantSuperAdmin()],
            'password' => 'required|min:8|confirmed',
        ], $this->validationMessages());

        // Super Admin accounts aren't a plan-billable hospital seat — every
        // other role counts against either the general "user" pool or, for
        // patients, the dedicated patient limit. See
        // SUPER_ADMIN_USAGE_LIMIT_ENFORCEMENT_REPORT.md.
        $resource = match ($request->role) {
            'super_admin' => null,
            'patient' => 'patient',
            default => 'user',
        };

        if ($resource) {
            try {
                $this->limits->assertCanCreate($resource);
            } catch (\InvalidArgumentException $e) {
                return back()->with('error', $e->getMessage())->withInput();
            }
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        return redirect()->route('users.index')
            ->with('success', $request->name . ' added successfully!');
    }

    public function getJson(User $user)
    {
        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role'  => $user->roles->first()?->name,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'  => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'role'  => ['required', 'in:super_admin,admin,doctor,nurse,receptionist,lab_assistant,pharmacist,staff,patient', $this->onlySuperAdminMayGrantSuperAdmin()],
        ], $this->validationMessages());

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')
            ->with('success', $user->name . ' updated successfully!');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ], $this->validationMessages());

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('users.index')
            ->with('success', 'Password reset for ' . $user->name . '!');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'You cannot delete yourself!'], 403);
        }
        if ($user->hasRole('super_admin')) {
            return response()->json(['error' => 'Cannot delete super admin!'], 403);
        }

        try {
            $this->safelyDeleteUser($user);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Unable to delete this user because related records could not be cleared. Please try again.'], 500);
        }
    }
}