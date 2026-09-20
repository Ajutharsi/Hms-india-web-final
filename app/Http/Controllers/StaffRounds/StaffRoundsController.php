<?php

namespace App\Http\Controllers\StaffRounds;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\StaffRound;
use App\Models\RoundAssignment;
use App\Models\Ward;
use App\Models\User;
use Illuminate\Http\Request;

class StaffRoundsController extends Controller
{
    public function index()
    {
        $wards = Ward::where('status', 'active')->get();

        $staffList = User::role(['nurse', 'staff'])
            ->select('id', 'name')
            ->get();

        return view('staff-rounds.index', compact('wards', 'staffList'));
    }

    public function assignmentsData()
    {
        $assignments = RoundAssignment::with(['ward', 'staff', 'assignedBy'])
            ->where('status', 'active')
            ->get()
            ->map(function ($a) {
                return [
                    'id'              => $a->id,
                    'ward_name'       => $a->ward->name ?? 'N/A',
                    'ward_type'       => $a->ward->type ?? 'N/A',
                    'staff_name'      => $a->staff->name ?? 'N/A',
                    'assigned_by'     => $a->assignedBy->name ?? 'N/A',
                    'frequency_hours' => $a->frequency_hours,
                    'next_round_at'   => $a->next_round_at?->format('d M Y h:i A'),
                    'last_round_at'   => $a->last_round_at?->format('d M Y h:i A'),
                    'is_overdue'      => $a->isOverdue(),
                    'status'          => $a->status,
                ];
            });

        return response()->json(['data' => $assignments]);
    }

    public function assign(Request $request)
    {
        $request->validate([
            'ward_id'         => 'required|exists:wards,id',
            'staff_id'        => 'required|exists:users,id',
            'frequency_hours' => 'required|integer|min:1|max:24',
            'notes'           => 'nullable|string|max:1000',
        ], $this->validationMessages());

        // Check if already assigned
        $existing = RoundAssignment::where('ward_id', $request->ward_id)
            ->where('staff_id', $request->staff_id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return back()->with('error', 'This staff is already assigned to this ward!');
        }

        RoundAssignment::create([
            'ward_id'         => $request->ward_id,
            'staff_id'        => $request->staff_id,
            'assigned_by'     => auth()->id(),
            'frequency_hours' => (int) $request->frequency_hours,
            'next_round_at'   => now()->addHours((int) $request->frequency_hours),
            'notes'           => $request->notes,
            'status'          => 'active',
        ]);

        return back()->with('success', 'Round assignment created successfully!');
    }

    public function deactivate(RoundAssignment $assignment)
    {
        $assignment->update(['status' => 'inactive']);
        return back()->with('success', 'Assignment removed!');
    }

    public function myRounds()
    {
        return view('staff-rounds.my-rounds');
    }

    public function myRoundsData()
    {
        $assignments = RoundAssignment::with(['ward'])
            ->where('staff_id', auth()->id())
            ->where('status', 'active')
            ->get()
            ->map(function ($a) {
                // Get all admitted patients in this ward
                $patients = Patient::with(['currentAdmission.bed'])
                    ->whereHas('currentAdmission', function ($q) use ($a) {
                        $q->where('ward_id', $a->ward_id)->where('status', 'admitted');
                    })
                    ->where('status', 'active')
                    ->get();

                $minutesLeft = $a->next_round_at
                    ? now()->diffInMinutes($a->next_round_at, false)
                    : null;

                $alertStatus = 'ok';
                if ($a->isOverdue()) $alertStatus = 'danger';
                elseif ($minutesLeft !== null && $minutesLeft <= 15) $alertStatus = 'warning';

                return [
                    'id'              => $a->id,
                    'ward_name'       => $a->ward->name ?? 'N/A',
                    'ward_id'         => $a->ward_id,
                    'frequency_hours' => $a->frequency_hours,
                    'next_round_at'   => $a->next_round_at?->format('h:i A'),
                    'last_round_at'   => $a->last_round_at?->format('d M h:i A'),
                    'minutes_left'    => $minutesLeft,
                    'is_overdue'      => $a->isOverdue(),
                    'alert_status'    => $alertStatus,
                    'patients'        => $patients->map(fn($p) => [
                        'id'     => $p->id,
                        'name'   => $p->name,
                        'mrn'    => $p->mrn,
                        'bed_no' => $p->currentAdmission?->bed?->bed_no ?? 'N/A',
                    ]),
                ];
            })
            ->sortBy('minutes_left')
            ->values();

        return response()->json(['data' => $assignments]);
    }

    public function markRound(Request $request, Patient $patient)
    {
        $request->validate([
            'assignment_id'  => 'required|exists:round_assignments,id',
            'blood_pressure' => 'nullable|string|max:20',
            'temperature'    => 'nullable|string|max:20',
            'pulse_rate'     => 'nullable|string|max:20',
            'spo2'           => 'nullable|string|max:20',
            'notes'          => 'nullable|string|max:1000',
        ], $this->validationMessages());

        StaffRound::create([
            'patient_id'     => $patient->id,
            'staff_id'       => auth()->id(),
            'round_time'     => now(),
            'status'         => 'completed',
            'blood_pressure' => $request->blood_pressure,
            'temperature'    => $request->temperature,
            'pulse_rate'     => $request->pulse_rate,
            'spo2'           => $request->spo2,
            'notes'          => $request->notes,
        ]);

        // Update assignment
        $assignment = RoundAssignment::find($request->assignment_id);
        $assignment->update([
            'last_round_at' => now(),
            'next_round_at' => now()->addHours((int) $assignment->frequency_hours),
        ]);

        return back()->with('success', 'Round marked complete for ' . $patient->name . '!');
    }

    public function alertCount()
    {
        $count = RoundAssignment::where('staff_id', auth()->id())
            ->where('status', 'active')
            ->where('next_round_at', '<=', now())
            ->count();

        return response()->json(['count' => $count]);
    }

    public function history(Patient $patient)
    {
        $rounds = StaffRound::with('staff')
            ->where('patient_id', $patient->id)
            ->orderBy('round_time', 'desc')
            ->take(20)
            ->get();

        return response()->json(['data' => $rounds]);
    }
}