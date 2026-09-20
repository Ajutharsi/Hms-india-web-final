<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Services\SubscriptionLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class StaffController extends Controller
{
    public function __construct(private readonly SubscriptionLimitService $limits)
    {
    }


    private function authorizeStaffAccess(User $staffUser): void
    {
        $user = auth()->user();

        if ($user->hasRole(['super_admin', 'admin'])) {
            return;
        }

        abort_if($user->id !== $staffUser->id, 403, 'You are not authorized to view this profile.');
    }

    public function index()
    {
        $departments = Department::all();
        $userUsage = $this->limits->resourceSummary('user');
        return view('staff.index', compact('departments', 'userUsage'));
    }

    public function data()
    {
        $defaultCurrency = config('hospital.currency', 'LKR');

        $query = User::role(['nurse', 'staff', 'doctor', 'receptionist', 'lab_assistant', 'pharmacist'])
            ->with(['roles', 'department']);

        return DataTables::of($query)
            ->addColumn('role', fn($u) => $u->roles->first()?->name ?? 'N/A')
            ->addColumn('department_name', fn($u) => $u->department?->name ?? 'N/A')
            ->addColumn('total_pay', fn($u) => ($u->pay_rate ?? 0) + ($u->allowance_transport ?? 0) + ($u->allowance_meal ?? 0) + ($u->allowance_housing ?? 0))
            ->addColumn('actions', fn($u) => '
                <div class="d-flex gap-1">
                    <a href="/staff/' . $u->id . '" class="btn btn-sm btn-icon btn-label-info" title="View">
                        <i class="icon-base ti tabler-eye"></i>
                    </a>
                    <button onclick="editStaff(' . $u->id . ')" class="btn btn-sm btn-icon btn-label-warning" title="Edit">
                        <i class="icon-base ti tabler-edit"></i>
                    </button>
                    <button onclick="deleteStaff(' . $u->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete">
                        <i class="icon-base ti tabler-trash"></i>
                    </button>
                </div>
            ')
            ->filterColumn('role', fn($q, $kw) => $q->whereHas('roles', fn($r) => $r->where('name', 'like', "%{$kw}%")))
            ->editColumn('employee_id', fn($u) => $u->employee_id ?? 'N/A')
            ->editColumn('shift', fn($u) => $u->shift ?? 'N/A')
            ->editColumn('employment_status', fn($u) => $u->employment_status ?? 'active')
            ->editColumn('leave_balance', fn($u) => $u->leave_balance ?? 0)
            ->orderColumn('total_pay', 'pay_rate $1')
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                    => 'required|string|min:2|max:255',
            'email'                   => 'required|email|max:255|unique:users,email',
            'phone'                   => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'role'                    => 'required|in:doctor,nurse,receptionist,lab_assistant,pharmacist,staff',
            'department_id'           => 'nullable|exists:departments,id',
            'joining_date'            => 'nullable|date',
            'dob'                     => 'nullable|date|before:today',
            'gender'                  => 'nullable|in:male,female,other',
            'blood_group'             => 'nullable|string|max:255',
            'national_id'             => 'nullable|string|max:255',
            'address'                 => 'nullable|string|max:500',
            'employment_type'         => 'nullable|in:full_time,part_time,contract',
            'shift'                   => 'nullable|in:morning,evening,night',
            'qualification'           => 'nullable|string|max:255',
            'license_no'              => 'nullable|string|max:255',
            'experience_years'        => 'nullable|integer|min:0|max:50',
            'pay_type'                => 'nullable|in:hourly,daily,monthly',
            'pay_rate'                => 'nullable|numeric|min:0',
            'currency'                => 'nullable|string|max:255',
            'allowance_transport'     => 'nullable|numeric|min:0',
            'allowance_meal'          => 'nullable|numeric|min:0',
            'allowance_housing'       => 'nullable|numeric|min:0',
            'bank_account_name'       => 'nullable|string|max:255',
            'bank_account_no'         => 'nullable|string|max:255',
            'bank_name'               => 'nullable|string|max:255',
            'payment_method'          => 'nullable|string|max:255',
            'emergency_contact_name'  => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'nok_relationship'        => 'nullable|string|max:255',
            'leave_balance'           => 'nullable|integer|min:0',
            'profile_photo'           => 'nullable|image|max:2048',
            'doc_license'             => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'doc_certificate'         => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'doc_national_id'         => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'doc_other'               => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ], $this->validationMessages());

        try {
            $this->limits->assertCanCreate('user');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $prefix          = config('hospital.employee_prefix', 'EMP');
        $lastUser        = User::whereNotNull('employee_id')->orderBy('id', 'desc')->first();
        $number          = $lastUser ? (intval(substr($lastUser->employee_id, -4)) + 1) : 1;
        $employeeId      = $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
        $defaultPassword = $employeeId . '@HMS';

        $profilePhoto   = $request->hasFile('profile_photo')   ? $request->file('profile_photo')->store('staff/photos', 'public')  : null;
        $docLicense     = $request->hasFile('doc_license')     ? $request->file('doc_license')->store('staff/docs', 'public')      : null;
        $docCertificate = $request->hasFile('doc_certificate') ? $request->file('doc_certificate')->store('staff/docs', 'public')  : null;
        $docNationalId  = $request->hasFile('doc_national_id') ? $request->file('doc_national_id')->store('staff/docs', 'public')  : null;
        $docOther       = $request->hasFile('doc_other')       ? $request->file('doc_other')->store('staff/docs', 'public')        : null;

        $user = User::create([
            'employee_id'             => $employeeId,
            'name'                    => $request->name,
            'email'                   => $request->email,
            'phone'                   => $request->phone,
            'password'                => Hash::make($defaultPassword),
            'department_id'           => $request->department_id,
            'joining_date'            => $request->joining_date,
            'dob'                     => $request->dob,
            'gender'                  => $request->gender,
            'blood_group'             => $request->blood_group,
            'national_id'             => $request->national_id,
            'address'                 => $request->address,
            'employment_type'         => $request->employment_type ?? 'full_time',
            'shift'                   => $request->shift,
            'qualification'           => $request->qualification,
            'license_no'              => $request->license_no,
            'experience_years'        => $request->experience_years ?? 0,
            'pay_type'                => $request->pay_type ?? 'monthly',
            'pay_rate'                => $request->pay_rate ?? 0,
            'currency'                => $request->currency ?? config('hospital.currency', 'LKR'),
            'allowance_transport'     => $request->allowance_transport ?? 0,
            'allowance_meal'          => $request->allowance_meal ?? 0,
            'allowance_housing'       => $request->allowance_housing ?? 0,
            'bank_account_name'       => $request->bank_account_name,
            'bank_account_no'         => $request->bank_account_no,
            'bank_name'               => $request->bank_name,
            'payment_method'          => $request->payment_method,
            'employment_status'       => 'active',
            'leave_balance'           => $request->leave_balance ?? 14,
            'emergency_contact_name'  => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'nok_relationship'        => $request->nok_relationship,
            'profile_photo'           => $profilePhoto,
            'doc_license'             => $docLicense,
            'doc_certificate'         => $docCertificate,
            'doc_national_id'         => $docNationalId,
            'doc_other'               => $docOther,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('staff.index')
            ->with('success', $request->name . ' added! (ID: ' . $employeeId . ' | Default Password: ' . $defaultPassword . ')');
    }

    public function show(User $user)
    {
        $this->authorizeStaffAccess($user);

        $user->load(['roles', 'department']);

        $recentAttendance = \App\Models\Attendance::where('user_id', $user->id)
            ->orderByDesc('attendance_date')->take(10)->get();
        $recentLeaves = \App\Models\LeaveRequest::where('user_id', $user->id)
            ->orderByDesc('created_at')->take(10)->get();

        return view('staff.show', compact('user', 'recentAttendance', 'recentLeaves'));
    }

    public function getJson(User $user)
    {
        $this->authorizeStaffAccess($user);

        return response()->json([
            'id'                      => $user->id,
            'name'                    => $user->name,
            'email'                   => $user->email,
            'phone'                   => $user->phone,
            'role'                    => $user->roles->first()?->name,
            'department_id'           => $user->department_id,
            'joining_date'            => $user->joining_date,
            'dob'                     => $user->dob,
            'gender'                  => $user->gender,
            'blood_group'             => $user->blood_group,
            'national_id'             => $user->national_id,
            'address'                 => $user->address,
            'employment_type'         => $user->employment_type,
            'shift'                   => $user->shift,
            'qualification'           => $user->qualification,
            'license_no'              => $user->license_no,
            'experience_years'        => $user->experience_years,
            'pay_type'                => $user->pay_type,
            'pay_rate'                => $user->pay_rate,
            'currency'                => $user->currency,
            'allowance_transport'     => $user->allowance_transport,
            'allowance_meal'          => $user->allowance_meal,
            'allowance_housing'       => $user->allowance_housing,
            'bank_account_name'       => $user->bank_account_name,
            'bank_account_no'         => $user->bank_account_no,
            'bank_name'               => $user->bank_name,
            'payment_method'          => $user->payment_method,
            'employment_status'       => $user->employment_status,
            'leave_balance'           => $user->leave_balance,
            'emergency_contact_name'  => $user->emergency_contact_name,
            'emergency_contact_phone' => $user->emergency_contact_phone,
            'nok_relationship'        => $user->nok_relationship,
            'profile_photo'           => $user->profile_photo,
            'doc_license'             => $user->doc_license,
            'doc_certificate'         => $user->doc_certificate,
            'doc_national_id'         => $user->doc_national_id,
            'doc_other'               => $user->doc_other,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'                    => 'required|string|min:2|max:255',
            'email'                   => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone'                   => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'role'                    => 'required|in:doctor,nurse,receptionist,lab_assistant,pharmacist,staff',
            'department_id'           => 'nullable|exists:departments,id',
            'joining_date'            => 'nullable|date',
            'dob'                     => 'nullable|date|before:today',
            'gender'                  => 'nullable|in:male,female,other',
            'blood_group'             => 'nullable|string|max:255',
            'national_id'             => 'nullable|string|max:255',
            'address'                 => 'nullable|string|max:500',
            'employment_type'         => 'nullable|in:full_time,part_time,contract',
            'shift'                   => 'nullable|in:morning,evening,night',
            'qualification'           => 'nullable|string|max:255',
            'license_no'              => 'nullable|string|max:255',
            'experience_years'        => 'nullable|integer|min:0|max:50',
            'pay_type'                => 'nullable|in:hourly,daily,monthly',
            'pay_rate'                => 'nullable|numeric|min:0',
            'currency'                => 'nullable|string|max:255',
            'allowance_transport'     => 'nullable|numeric|min:0',
            'allowance_meal'          => 'nullable|numeric|min:0',
            'allowance_housing'       => 'nullable|numeric|min:0',
            'bank_account_name'       => 'nullable|string|max:255',
            'bank_account_no'         => 'nullable|string|max:255',
            'bank_name'               => 'nullable|string|max:255',
            'payment_method'          => 'nullable|string|max:255',
            'employment_status'       => 'nullable|in:active,on_leave,resigned,terminated',
            'leave_balance'           => 'nullable|integer|min:0',
            'emergency_contact_name'  => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'nok_relationship'        => 'nullable|string|max:255',
            'profile_photo'           => 'nullable|image|max:2048',
            'doc_license'             => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'doc_certificate'         => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'doc_national_id'         => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'doc_other'               => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ], $this->validationMessages());

        $data = [
            'name'                    => $request->name,
            'email'                   => $request->email,
            'phone'                   => $request->phone,
            'department_id'           => $request->department_id,
            'joining_date'            => $request->joining_date,
            'dob'                     => $request->dob,
            'gender'                  => $request->gender,
            'blood_group'             => $request->blood_group,
            'national_id'             => $request->national_id,
            'address'                 => $request->address,
            // These columns are NOT NULL with a DB-level default (applied
            // only on INSERT) — on UPDATE, submitting the form without them
            // would otherwise explicitly null them out and violate the
            // constraint. Fall back to the current value instead.
            'employment_type'         => $request->employment_type ?? $user->employment_type,
            'shift'                   => $request->shift,
            'qualification'           => $request->qualification,
            'license_no'              => $request->license_no,
            'experience_years'        => $request->experience_years ?? $user->experience_years,
            'pay_type'                => $request->pay_type ?? $user->pay_type,
            'pay_rate'                => $request->pay_rate ?? $user->pay_rate,
            'currency'                => $request->currency ?? $user->currency,
            'allowance_transport'     => $request->allowance_transport ?? $user->allowance_transport,
            'allowance_meal'          => $request->allowance_meal ?? $user->allowance_meal,
            'allowance_housing'       => $request->allowance_housing ?? $user->allowance_housing,
            'bank_account_name'       => $request->bank_account_name,
            'bank_account_no'         => $request->bank_account_no,
            'bank_name'               => $request->bank_name,
            'payment_method'          => $request->payment_method,
            'employment_status'       => $request->employment_status ?? $user->employment_status,
            'leave_balance'           => $request->leave_balance ?? $user->leave_balance,
            'emergency_contact_name'  => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'nok_relationship'        => $request->nok_relationship,
        ];

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) Storage::disk('public')->delete($user->profile_photo);
            $data['profile_photo'] = $request->file('profile_photo')->store('staff/photos', 'public');
        }
        if ($request->hasFile('doc_license')) {
            if ($user->doc_license) Storage::disk('public')->delete($user->doc_license);
            $data['doc_license'] = $request->file('doc_license')->store('staff/docs', 'public');
        }
        if ($request->hasFile('doc_certificate')) {
            if ($user->doc_certificate) Storage::disk('public')->delete($user->doc_certificate);
            $data['doc_certificate'] = $request->file('doc_certificate')->store('staff/docs', 'public');
        }
        if ($request->hasFile('doc_national_id')) {
            if ($user->doc_national_id) Storage::disk('public')->delete($user->doc_national_id);
            $data['doc_national_id'] = $request->file('doc_national_id')->store('staff/docs', 'public');
        }
        if ($request->hasFile('doc_other')) {
            if ($user->doc_other) Storage::disk('public')->delete($user->doc_other);
            $data['doc_other'] = $request->file('doc_other')->store('staff/docs', 'public');
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        return redirect()->route('staff.index')
            ->with('success', $user->name . ' updated successfully!');
    }

    public function destroy(User $user)
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return response()->json(['error' => 'Cannot delete admin users!'], 403);
        }

        try {
            $this->safelyDeleteUser($user);

            // Delete files only after the DB delete succeeds
            foreach (['profile_photo', 'doc_license', 'doc_certificate', 'doc_national_id', 'doc_other'] as $field) {
                if ($user->$field) Storage::disk('public')->delete($user->$field);
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Unable to delete this staff member because related records could not be cleared. Please try again.'], 500);
        }
    }
}