<?php

namespace App\Http\Controllers\FoodIntake;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\DietPlan;
use App\Models\FoodLog;
use Illuminate\Http\Request;

class FoodIntakeController extends Controller
{
    public function index()
    {
        return view('food-intake.index');
    }

    public function data()
    {
        $patients = Patient::with(['dietPlan', 'currentAdmission.ward', 'currentAdmission.bed', 'todayFoodLogs'])
            ->where('patient_type', 'ip')
            ->where('status', 'active')
            ->get()
            ->map(function ($p) {
                $meals = ['breakfast', 'lunch', 'snacks', 'dinner'];
                $logs  = $p->todayFoodLogs->keyBy('meal_type');

                $lastMeal = null;
                foreach (array_reverse($meals) as $meal) {
                    if (isset($logs[$meal])) {
                        $lastMeal = [
                            'meal_type' => $meal,
                            'status'    => $logs[$meal]->status,
                        ];
                        break;
                    }
                }

                return [
                    'id'          => $p->id,
                    'name'        => $p->name,
                    'mrn'         => $p->mrn,
                    'ward_name'   => $p->currentAdmission?->ward?->name,
                    'bed_no'      => $p->currentAdmission?->bed?->bed_no,
                    'diet_type'   => $p->dietPlan?->diet_type ?? 'Not set',
                    'last_meal'   => $lastMeal,
                    'meals_today' => $logs->count(),
                ];
            });

        return response()->json(['data' => $patients]);
    }

    public function show(Patient $patient)
    {
        $patient->load(['dietPlan', 'currentAdmission.ward', 'currentAdmission.bed']);

        $foodLogs = FoodLog::with('loggedBy')
            ->where('patient_id', $patient->id)
            ->orderBy('log_date', 'desc')
            ->orderByRaw("FIELD(meal_type, 'dinner', 'snacks', 'lunch', 'breakfast')")
            ->take(30)
            ->get()
            ->groupBy('log_date');

        return view('food-intake.show', compact('patient', 'foodLogs'));
    }

    public function storeDietPlan(Request $request, Patient $patient)
    {
        $request->validate([
            'diet_type'    => 'required|in:normal,diabetic,liquid,soft,low_salt,npo,other',
            'restrictions' => 'nullable|string|max:1000',
            'notes'        => 'nullable|string|max:1000',
        ], $this->validationMessages());

        // Deactivate old plan
        DietPlan::where('patient_id', $patient->id)->update(['status' => 'inactive']);

        DietPlan::create([
            'patient_id'   => $patient->id,
            'diet_type'    => $request->diet_type,
            'restrictions' => $request->restrictions,
            'notes'        => $request->notes,
            'status'       => 'active',
        ]);

        return back()->with('success', 'Diet plan updated!');
    }

    public function logMeal(Request $request, Patient $patient)
    {
        $request->validate([
            'meal_type' => 'required|in:breakfast,lunch,snacks,dinner',
            'status'    => 'required|in:pending,given,refused,partial',
            'quantity'  => 'nullable|in:full,half,none',
            'notes'     => 'nullable|string|max:1000',
        ], $this->validationMessages());

        FoodLog::updateOrCreate(
            [
                'patient_id' => $patient->id,
                'log_date'   => today(),
                'meal_type'  => $request->meal_type,
            ],
            [
                'logged_by' => auth()->id(),
                'status'    => $request->status,
                'quantity'  => $request->quantity,
                'notes'     => $request->notes,
            ]
        );

        return back()->with('success', ucfirst($request->meal_type) . ' logged successfully!');
    }
}