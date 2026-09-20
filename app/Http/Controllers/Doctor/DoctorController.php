<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use App\Services\SubscriptionLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class DoctorController extends Controller
{
    public function __construct(private readonly SubscriptionLimitService $limits)
    {
    }

    public function index()
    {
        return view('doctors.index', [
            'doctorUsage' => $this->limits->resourceSummary('doctor'),
        ]);
    }

    public function data()
{
    $query = Doctor::query()->latest();

    return DataTables::of($query)
        ->addColumn('photo_url', fn ($d) => $d->photo_url)
        ->addColumn('doc_badges', function ($d) {
            $badges = '';
            if ($d->license_file_name) $badges .= '<span class="badge bg-label-success me-1" title="License"><i class="icon-base ti tabler-license icon-sm"></i></span>';
            if ($d->certificate_file_name) $badges .= '<span class="badge bg-label-warning me-1" title="Certificate"><i class="icon-base ti tabler-certificate icon-sm"></i></span>';
            if ($d->id_file_name) $badges .= '<span class="badge bg-label-info" title="ID"><i class="icon-base ti tabler-id icon-sm"></i></span>';
            return $badges ?: '<span class="text-muted small">None</span>';
        })
        ->addColumn('status_badge', fn($d) => '<span class="badge bg-label-' . ($d->status === 'active' ? 'success' : 'danger') . '">' . $d->status . '</span>')
        ->addColumn('actions', fn($d) => '
            <div class="d-flex gap-2">
                <a href="/doctors/' . $d->id . '" class="btn btn-sm btn-icon btn-label-info" title="View">
                    <i class="icon-base ti tabler-eye"></i>
                </a>
                <button onclick="editDoctor(' . $d->id . ')" class="btn btn-sm btn-icon btn-label-warning" title="Edit">
                    <i class="icon-base ti tabler-edit"></i>
                </button>
                <button onclick="deleteDoctor(' . $d->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete">
                    <i class="icon-base ti tabler-trash"></i>
                </button>
            </div>
        ')
        ->rawColumns(['doc_badges', 'status_badge', 'actions'])   
        ->make(true);
}


    public function store(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|min:2|max:255',
            'email'               => 'required|email|max:255|unique:doctors,email',
            'phone'               => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'specialization'      => 'required|string|max:255',
            'department'          => 'nullable|string|max:255',
            'qualification'       => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255|unique:doctors,registration_number',
            'consultation_fee'    => 'nullable|numeric|min:0',
            'status'              => 'nullable|in:active,inactive',
            'photo'               => 'nullable|mimes:jpg,jpeg,png,webp|max:2048',
            'license_file'        => 'nullable|mimes:pdf,jpg,jpeg,png|max:10240',
            'certificate_file'    => 'nullable|mimes:pdf,jpg,jpeg,png|max:10240',
            'id_file'             => 'nullable|mimes:pdf,jpg,jpeg,png|max:10240',
        ], $this->validationMessages());

        try {
            $this->limits->assertCanCreate('doctor');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        // Handle file uploads
        $photo           = $request->hasFile('photo')            ? $request->file('photo')->store('doctors/photos', 'public')       : null;
        $licenseFile     = $request->hasFile('license_file')     ? $request->file('license_file')->store('doctors/docs', 'public')     : null;
        $certificateFile = $request->hasFile('certificate_file') ? $request->file('certificate_file')->store('doctors/docs', 'public') : null;
        $idFile          = $request->hasFile('id_file')          ? $request->file('id_file')->store('doctors/docs', 'public')          : null;

        $doctor = Doctor::create([
            'name'                  => $request->name,
            'email'                 => $request->email,
            'phone'                 => $request->phone,
            'specialization'        => $request->specialization,
            'department'            => $request->department,
            'qualification'         => $request->qualification,
            'registration_number'   => $request->registration_number,
            'consultation_fee'      => $request->consultation_fee ?? 0,
            'status'                => $request->status ?? 'active',
            'photo'                 => $photo,
            'license_file'          => $licenseFile,
            'license_file_name'     => $request->hasFile('license_file') ? $request->file('license_file')->getClientOriginalName() : null,
            'certificate_file'      => $certificateFile,
            'certificate_file_name' => $request->hasFile('certificate_file') ? $request->file('certificate_file')->getClientOriginalName() : null,
            'id_file'               => $idFile,
            'id_file_name'          => $request->hasFile('id_file') ? $request->file('id_file')->getClientOriginalName() : null,
        ]);

        $doctorId = $doctor->doctor_id;

        // Auto create user account if enabled in settings
        if (config('hospital.auto_doctor_login')) {
            $defaultPassword = $doctorId . '@HMS';
            $existingUser = User::where('email', $request->email)->first();
            if (!$existingUser) {
                $user = User::create([
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'password' => Hash::make($defaultPassword),
                ]);
                $user->assignRole('doctor');
                session()->flash('user_created', 'Doctor login created! Email: ' . $request->email . ' | Password: ' . $defaultPassword);
            }
        }

        return redirect()->route('doctors.index')
            ->with('success', 'Dr. ' . $request->name . ' added successfully! (ID: ' . $doctorId . ')');
    }

    public function show(Doctor $doctor)
    {
        $doctor->load(['schedules']);
        return view('doctors.show', compact('doctor'));
    }

    public function getJson(Doctor $doctor)
    {
        return response()->json([
            'id'                  => $doctor->id,
            'name'                => $doctor->name,
            'email'               => $doctor->email,
            'phone'               => $doctor->phone,
            'specialization'      => $doctor->specialization,
            'department'          => $doctor->department,
            'qualification'       => $doctor->qualification,
            'registration_number' => $doctor->registration_number,
            'consultation_fee'    => $doctor->consultation_fee,
            'status'              => $doctor->status,
            'photo_url'             => $doctor->photo_url,
            'license_file_name'     => $doctor->license_file_name,
            'certificate_file_name' => $doctor->certificate_file_name,
            'id_file_name'          => $doctor->id_file_name,
        ]);
    }

    public function update(Request $request, Doctor $doctor)
    {
        $request->validate([
            'name'                => 'required|string|min:2|max:255',
            'email'               => 'required|email|max:255|unique:doctors,email,' . $doctor->id,
            'phone'               => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'specialization'      => 'required|string|max:255',
            'department'          => 'nullable|string|max:255',
            'qualification'       => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255|unique:doctors,registration_number,' . $doctor->id,
            'consultation_fee'    => 'nullable|numeric|min:0',
            'status'              => 'nullable|in:active,inactive',
            'photo'               => 'nullable|mimes:jpg,jpeg,png,webp|max:2048',
            'license_file'        => 'nullable|mimes:pdf,jpg,jpeg,png|max:10240',
            'certificate_file'    => 'nullable|mimes:pdf,jpg,jpeg,png|max:10240',
            'id_file'             => 'nullable|mimes:pdf,jpg,jpeg,png|max:10240',
        ], $this->validationMessages());

        $data = [
            'name'                => $request->name,
            'email'               => $request->email,
            'phone'               => $request->phone,
            'specialization'      => $request->specialization,
            'department'          => $request->department,
            'qualification'       => $request->qualification,
            'registration_number' => $request->registration_number,
            'consultation_fee'    => $request->consultation_fee,
            'status'              => $request->status,
        ];

        if ($request->hasFile('photo')) {
            // Legacy seeded photos live under assets/doctors/ directly in public/,
            // not on the storage disk — never pass those to Storage::delete().
            if ($doctor->photo && !str_starts_with($doctor->photo, 'assets/')) {
                Storage::disk('public')->delete($doctor->photo);
            }
            $data['photo'] = $request->file('photo')->store('doctors/photos', 'public');
        }

        foreach (['license_file' => 'license_file_name', 'certificate_file' => 'certificate_file_name', 'id_file' => 'id_file_name'] as $field => $nameField) {
            if ($request->hasFile($field)) {
                if ($doctor->$field) Storage::disk('public')->delete($doctor->$field);
                $data[$field]     = $request->file($field)->store('doctors/docs', 'public');
                $data[$nameField] = $request->file($field)->getClientOriginalName();
            }
        }

        $doctor->update($data);

        // Update user account name if exists
        $user = User::where('email', $doctor->email)->first();
        if ($user) {
            $user->update(['name' => $request->name]);
        }

        // back() so an edit submitted from the doctor's own detail page returns
        // there instead of always bouncing to the index list.
        return back()->with('success', 'Dr. ' . $request->name . ' updated successfully!');
    }

    /** Streams a doctor's uploaded license/certificate/ID document (public disk) as a download. */
    public function download(Doctor $doctor, string $type)
    {
        $fields = [
            'license'     => ['license_file', 'license_file_name'],
            'certificate' => ['certificate_file', 'certificate_file_name'],
            'id'          => ['id_file', 'id_file_name'],
        ];

        abort_unless(isset($fields[$type]), 404);
        [$pathField, $nameField] = $fields[$type];

        abort_unless($doctor->$pathField && Storage::disk('public')->exists($doctor->$pathField), 404);

        return Storage::disk('public')->download($doctor->$pathField, $doctor->$nameField ?: basename($doctor->$pathField));
    }

    public function destroy(Doctor $doctor)
    {
        if ($doctor->photo && !str_starts_with($doctor->photo, 'assets/')) Storage::disk('public')->delete($doctor->photo);
        if ($doctor->license_file) Storage::disk('public')->delete($doctor->license_file);
        if ($doctor->certificate_file) Storage::disk('public')->delete($doctor->certificate_file);
        if ($doctor->id_file) Storage::disk('public')->delete($doctor->id_file);

        $doctor->delete();
        return response()->json(['success' => true]);
    }

    public function addSchedule(Request $request, Doctor $doctor)
    {
        $request->validate([
            'day'        => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
            'max_patients' => 'nullable|integer|min:1',
        ], $this->validationMessages());

        $doctor->schedules()->create([
            'day'          => $request->day,
            'start_time'   => $request->start_time,
            'end_time'     => $request->end_time,
            'max_patients' => $request->max_patients ?? 20,
        ]);

        return back()->with('success', 'Schedule added!');
    }

    public function deleteSchedule($schedule)
    {
        \App\Models\DoctorSchedule::findOrFail($schedule)->delete();
        return back()->with('success', 'Schedule removed!');
    }
}