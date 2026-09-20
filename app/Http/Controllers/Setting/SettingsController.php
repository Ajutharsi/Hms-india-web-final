<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\HospitalSetting;
use App\Models\ActivityLog;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = HospitalSetting::get();
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            // Hospital Info
            'hospital_name'        => 'required|string|max:255',
            'hospital_code'        => 'nullable|string|max:50',
            'address'              => 'nullable|string|max:500',
            'city'                 => 'nullable|string|max:255',
            'country'              => 'nullable|string|max:255',
            'phone'                => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'email'                => 'nullable|email|max:255',
            'website'              => 'nullable|string|max:255',
            'registration_number'  => 'nullable|string|max:100',
            'tax_number'           => 'nullable|string|max:100',
            'footer_note'          => 'nullable|string|max:1000',
            'logo'                 => 'nullable|image|max:2048',

            // System Settings
            'currency'             => 'nullable|in:LKR,USD,INR,EUR',
            'date_format'          => 'nullable|string|max:20',
            'timezone'             => 'nullable|string|max:100',
            'mrn_prefix'           => 'nullable|string|max:10',
            'doctor_id_prefix'     => 'nullable|string|max:10',
            'employee_id_prefix'   => 'nullable|string|max:10',

            // Notifications & Alerts
            'low_stock_threshold'  => 'nullable|integer|min:1',
            'near_expiry_days'     => 'nullable|integer|min:1',
            'round_alert_hours'    => 'nullable|integer|min:1|max:24',

            // Module Features
            'features'             => 'nullable|array',
            'features.*'           => 'boolean',
        ], $this->validationMessages());

        $settings = HospitalSetting::first();
        $data     = collect($validated)->except(['logo', 'features'])->toArray();

        $data['auto_patient_login'] = $request->boolean('auto_patient_login');
        $data['auto_doctor_login']  = $request->boolean('auto_doctor_login');

        $features = HospitalSetting::DEFAULT_FEATURES;
        foreach (array_keys($features) as $key) {
            $features[$key] = $request->boolean("features.$key");
        }
        $data['features_enabled'] = $features;

        if ($request->hasFile('logo')) {
            if ($settings && $settings->logo) {
                Storage::disk('public')->delete($settings->logo);
            }
            $data['logo'] = $request->file('logo')->store('hospital', 'public');
        }

        if ($settings) {
            $settings->update($data);
        } else {
            HospitalSetting::create($data);
        }

        ActivityLog::log('updated', 'Settings', 'Hospital settings updated');

        return redirect()->route('settings.index')
            ->with('success', 'Hospital settings updated!');
    }

    public function departments()
    {
        $departments = Department::latest()->get();
        return view('settings.departments', compact('departments'));
    }

    public function storeDepartment(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|min:2|max:255|unique:departments,name',
            'code'        => 'nullable|string|max:20',
            'description' => 'nullable|string|max:1000',
            'head_doctor' => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'location'    => 'nullable|string|max:255',
            'status'      => 'nullable|in:active,inactive',
        ], $this->validationMessages());

        Department::create([
            'name'        => $request->name,
            'code'        => $request->code,
            'description' => $request->description,
            'head_doctor' => $request->head_doctor,
            'phone'       => $request->phone,
            'location'    => $request->location,
            'status'      => $request->status ?? 'active',
        ]);

        return redirect()->route('settings.departments')
            ->with('success', 'Department added successfully!');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $request->validate([
            'name'        => 'required|string|min:2|max:255|unique:departments,name,' . $department->id,
            'code'        => 'nullable|string|max:20',
            'description' => 'nullable|string|max:1000',
            'head_doctor' => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'location'    => 'nullable|string|max:255',
            'status'      => 'nullable|in:active,inactive',
        ], $this->validationMessages());

        $department->update([
            'name'        => $request->name,
            'code'        => $request->code,
            'description' => $request->description,
            'head_doctor' => $request->head_doctor,
            'phone'       => $request->phone,
            'location'    => $request->location,
            'status'      => $request->status,
        ]);

        return redirect()->route('settings.departments')
            ->with('success', 'Department updated successfully!');
    }

    public function destroyDepartment(Department $department)
    {
        $department->delete();
        return response()->json(['success' => true]);
    }

    public function activityLog()
    {
        $logs = ActivityLog::latest()->paginate(20);
        return view('settings.activity-log', compact('logs'));
    }
}