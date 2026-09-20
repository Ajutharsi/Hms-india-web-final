<?php

namespace App\Http\Controllers\FluidIntake;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\FluidLog;
use Illuminate\Http\Request;

class FluidIntakeController extends Controller
{
    public function index()
    {
        return view('fluid-intake.index');
    }

    public function data()
    {
        $patients = Patient::with(['currentAdmission.ward', 'currentAdmission.bed', 'todayFluidLogs'])
            ->where('patient_type', 'ip')
            ->where('status', 'active')
            ->get()
            ->map(function ($p) {
                $intake  = $p->todayFluidLogs->where('flow_type', 'intake')->sum('amount_ml');
                $output  = $p->todayFluidLogs->where('flow_type', 'output')->sum('amount_ml');
                $balance = $intake - $output;

                return [
                    'id'         => $p->id,
                    'name'       => $p->name,
                    'mrn'        => $p->mrn,
                    'ward_name'  => $p->currentAdmission?->ward?->name,
                    'bed_no'     => $p->currentAdmission?->bed?->bed_no,
                    'intake'     => $intake,
                    'output'     => $output,
                    'balance'    => $balance,
                ];
            });

        return response()->json(['data' => $patients]);
    }

    public function show(Patient $patient)
    {
        $patient->load(['currentAdmission.ward', 'currentAdmission.bed']);

        $fluidLogs = FluidLog::with('loggedBy')
            ->where('patient_id', $patient->id)
            ->orderBy('log_date', 'desc')
            ->orderBy('log_time', 'desc')
            ->take(50)
            ->get()
            ->groupBy('log_date');

        $todayIntake  = FluidLog::where('patient_id', $patient->id)
            ->whereDate('log_date', today())->where('flow_type', 'intake')->sum('amount_ml');
        $todayOutput  = FluidLog::where('patient_id', $patient->id)
            ->whereDate('log_date', today())->where('flow_type', 'output')->sum('amount_ml');

        return view('fluid-intake.show', compact('patient', 'fluidLogs', 'todayIntake', 'todayOutput'));
    }

    public function logFluid(Request $request, Patient $patient)
    {
        $request->validate([
            'flow_type' => 'required|in:intake,output',
            'category'  => 'required|in:oral,iv,tube_feed,urine,vomit,drain,stool,other',
            'amount_ml' => 'required|integer|min:1',
            'log_time'  => 'required|date_format:H:i',
            'notes'     => 'nullable|string|max:1000',
        ], $this->validationMessages());

        FluidLog::create([
            'patient_id' => $patient->id,
            'logged_by'  => auth()->id(),
            'log_date'   => today(),
            'log_time'   => $request->log_time,
            'flow_type'  => $request->flow_type,
            'category'   => $request->category,
            'amount_ml'  => $request->amount_ml,
            'notes'      => $request->notes,
        ]);

        return back()->with('success', ucfirst($request->flow_type) . ' logged successfully!');
    }

    public function destroy(FluidLog $fluidLog)
    {
        $patientId = $fluidLog->patient_id;
        $fluidLog->delete();
        return redirect()->route('fluid-intake.show', $patientId)
            ->with('success', 'Entry deleted!');
    }
}