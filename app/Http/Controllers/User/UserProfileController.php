<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user()->load(['roles', 'department']);

        $myAttendance = \App\Models\Attendance::where('user_id', $user->id)
            ->orderByDesc('attendance_date')->take(10)->get();
        $myLeaves = \App\Models\LeaveRequest::where('user_id', $user->id)
            ->orderByDesc('created_at')->take(10)->get();

        return view('profile.show', compact('user', 'myAttendance', 'myLeaves'));
    }

    public function requestLeave(Request $request)
    {
        $request->validate([
            'leave_type' => 'required|in:sick,casual,annual,emergency,maternity,paternity',
            'from_date'  => 'required|date',
            'to_date'    => 'required|date|after_or_equal:from_date',
            'reason'     => 'required|string|max:1000',
        ], $this->validationMessages());

        $days = \Carbon\Carbon::parse($request->from_date)->diffInDays(\Carbon\Carbon::parse($request->to_date)) + 1;

        \App\Models\LeaveRequest::create([
            'user_id'    => auth()->id(),
            'leave_type' => $request->leave_type,
            'from_date'  => $request->from_date,
            'to_date'    => $request->to_date,
            'days'       => $days,
            'reason'     => $request->reason,
            'status'     => 'pending',
        ]);

        return redirect()->route('profile.show')->with('success', 'Leave request submitted!');
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'             => ['required', 'string', 'min:2', 'max:255'],
            'email'            => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'            => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password'         => ['nullable', 'confirmed', Password::defaults()],
            'profile_photo'    => ['nullable', 'image', 'max:2048'],
        ], $this->validationMessages([
            'current_password.current_password' => 'The current password is incorrect.',
        ]));

        
        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->update(['profile_photo' => $path]);
        }

        $user->update([
            'name'       => $request->name,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'last_login' => $user->last_login, 
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password), 'must_change_password' => false]);
        }

        return redirect()->route('profile.show')
            ->with('success', 'Profile updated successfully!');
    }
}