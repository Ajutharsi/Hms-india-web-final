<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Models\DrugDispensing;
use App\Models\DrugStock;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Services\DispensingService;
use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * JSON endpoints for the Flutter pharmacist app — mirrors the web's
 * PharmacistDashboardController + Pharmacy\DrugController +
 * Pharmacy\PharmacyController (dispensing). Drug Master + Dispensing
 * viewing are shared with the receptionist role on the web (same
 * `role:...|pharmacist|receptionist` middleware); dispensing *creation* is
 * pharmacist-only.
 */
class PharmacyController extends Controller
{
    use AuthorizesRoles;

    private function authPharmacist(Request $request): User
    {
        return $this->authRoles($request, ['pharmacist'], 'This account is not a pharmacist account.');
    }

    private function authPharmacistOrReceptionist(Request $request): User
    {
        return $this->authRoles($request, ['pharmacist', 'receptionist']);
    }

    // ============================================================
    // Dashboard (pharmacist only — receptionist's own dashboard is separate)
    // ============================================================

    public function dashboard(Request $request)
    {
        $this->authPharmacist($request);

        $lowStockThreshold = config('hospital.low_stock', 10);
        $nearExpiryDays = config('hospital.near_expiry', 30);

        return response()->json([
            'stats' => [
                'total_drugs' => Drug::count(),
                'low_stock' => Drug::where('current_stock', '<=', $lowStockThreshold)->count(),
                'dispensed_today' => DrugDispensing::whereDate('created_at', today())->count(),
                'near_expiry' => Drug::where('status', 'active')->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '<=', now()->addDays($nearExpiryDays))
                    ->whereDate('expiry_date', '>=', now())
                    ->count(),
            ],
        ]);
    }

    // ============================================================
    // Drug Master (both roles — full CRUD)
    // ============================================================

    public function drugs(Request $request)
    {
        $this->authPharmacistOrReceptionist($request);

        $drugs = Drug::when($request->filled('q'), fn ($q) => $q->where('drug_name', 'like', "%{$request->q}%")->orWhere('generic_name', 'like', "%{$request->q}%"))
            ->orderBy('drug_name')
            ->get()
            ->map(fn (Drug $d) => $this->drugPayload($d));

        return response()->json(['drugs' => $drugs]);
    }

    private function drugPayload(Drug $d): array
    {
        return [
            'id' => $d->id,
            'drug_code' => $d->drug_code,
            'drug_name' => $d->drug_name,
            'generic_name' => $d->generic_name,
            'category' => $d->category,
            'form' => $d->form,
            'strength' => $d->strength,
            'manufacturer' => $d->manufacturer,
            'unit_price' => (float) $d->unit_price,
            'min_stock_level' => $d->min_stock_level,
            'current_stock' => $d->current_stock,
            'expiry_date' => $d->expiry_date?->format('Y-m-d'),
            'status' => $d->status,
            'is_expired' => $d->isExpired(),
            'is_low_stock' => $d->isLowStock(),
            'is_near_expiry' => $d->isNearExpiry(),
        ];
    }

    private array $drugRules = [
        'drug_name' => ['required', 'string', 'min:2', 'max:255'],
        'generic_name' => ['nullable', 'string', 'max:255'],
        'category' => ['required', 'string', 'max:255'],
        'form' => ['required', 'string', 'max:255'],
        'strength' => ['nullable', 'string', 'max:255'],
        'manufacturer' => ['nullable', 'string', 'max:255'],
        'unit_price' => ['required', 'numeric', 'min:0'],
        'min_stock_level' => ['nullable', 'integer', 'min:0'],
        'expiry_date' => ['nullable', 'date'],
        'status' => ['nullable', 'in:active,inactive'],
    ];

    public function drugStore(Request $request)
    {
        $pharmacist = $this->authPharmacistOrReceptionist($request);

        $validated = $request->validate([...$this->drugRules, 'current_stock' => ['nullable', 'integer', 'min:0']]);

        $drug = Drug::create($validated);

        if (($validated['current_stock'] ?? 0) > 0) {
            DrugStock::create([
                'drug_id' => $drug->id,
                'type' => 'in',
                'quantity' => $validated['current_stock'],
                'balance' => $validated['current_stock'],
                'reason' => 'Initial Stock',
                'performed_by' => $pharmacist->id,
            ]);
        }

        return response()->json(['message' => 'Drug added successfully!', 'drug' => $this->drugPayload($drug)], 201);
    }

    public function drugUpdate(Request $request, Drug $drug)
    {
        $this->authPharmacistOrReceptionist($request);

        $validated = $request->validate($this->drugRules);
        $drug->update($validated);

        return response()->json(['message' => 'Drug updated!', 'drug' => $this->drugPayload($drug->fresh())]);
    }

    public function drugDestroy(Request $request, Drug $drug)
    {
        $this->authPharmacistOrReceptionist($request);
        $drug->delete();

        return response()->json(['message' => 'Drug deleted.']);
    }

    public function drugAddStock(Request $request, Drug $drug)
    {
        $pharmacist = $this->authPharmacistOrReceptionist($request);

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $newBalance = $drug->current_stock + $request->quantity;

        DrugStock::create([
            'drug_id' => $drug->id,
            'type' => 'in',
            'quantity' => $request->quantity,
            'balance' => $newBalance,
            'reason' => $request->reason,
            'notes' => $request->notes,
            'performed_by' => $pharmacist->id,
        ]);

        $drug->update(['current_stock' => $newBalance]);

        return response()->json(['message' => 'Stock added!', 'drug' => $this->drugPayload($drug->fresh())]);
    }

    public function drugStockHistory(Request $request, Drug $drug)
    {
        $this->authPharmacistOrReceptionist($request);

        $history = $drug->stocks()->with('performedBy')->latest()->get()->map(fn (DrugStock $s) => [
            'id' => $s->id,
            'type' => $s->type,
            'quantity' => $s->quantity,
            'balance' => $s->balance,
            'reason' => $s->reason,
            'notes' => $s->notes,
            'performed_by_name' => $s->performedBy?->name,
            'created_at' => $s->created_at,
        ]);

        return response()->json(['history' => $history]);
    }

    // ============================================================
    // Dispensing (viewing both roles; creating pharmacist only)
    // ============================================================

    public function dispensingList(Request $request)
    {
        $this->authPharmacistOrReceptionist($request);

        $items = DrugDispensing::with(['patient:id,name,mrn', 'prescription:id,prescription_no'])
            ->withCount('items')
            ->latest()
            ->take(150)
            ->get()
            ->map(fn (DrugDispensing $d) => [
                'id' => $d->id,
                'dispensing_no' => $d->dispensing_no,
                'patient_id' => $d->patient_id,
                'patient_name' => $d->patient?->name,
                'patient_mrn' => $d->patient?->mrn,
                'prescription_no' => $d->prescription?->prescription_no,
                'total_amount' => (float) $d->total_amount,
                'status' => $d->status,
                'items_count' => $d->items_count,
                'dispensed_date' => $d->dispensed_date,
            ]);

        return response()->json(['dispensings' => $items]);
    }

    public function dispensingDetail(Request $request, DrugDispensing $dispensing)
    {
        $this->authPharmacistOrReceptionist($request);
        $dispensing->load(['patient', 'prescription', 'items.drug', 'dispensedBy']);

        return response()->json([
            'id' => $dispensing->id,
            'dispensing_no' => $dispensing->dispensing_no,
            'patient_name' => $dispensing->patient?->name,
            'patient_mrn' => $dispensing->patient?->mrn,
            'prescription_no' => $dispensing->prescription?->prescription_no,
            'total_amount' => (float) $dispensing->total_amount,
            'status' => $dispensing->status,
            'notes' => $dispensing->notes,
            'dispensed_date' => $dispensing->dispensed_date,
            'dispensed_by_name' => $dispensing->dispensedBy?->name,
            'items' => $dispensing->items->map(fn ($item) => [
                'medicine_name' => $item->medicine_name,
                'quantity_prescribed' => $item->quantity_prescribed,
                'quantity_dispensed' => $item->quantity_dispensed,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
            ]),
        ]);
    }

    public function dispensingFromPrescription(Request $request, Prescription $prescription)
    {
        $this->authPharmacistOrReceptionist($request);
        $prescription->load(['patient', 'doctor', 'items']);

        return response()->json([
            'prescription_id' => $prescription->id,
            'prescription_no' => $prescription->prescription_no,
            'patient_id' => $prescription->patient_id,
            'patient_name' => $prescription->patient?->name,
            'doctor_name' => $prescription->doctor?->name,
            'items' => $prescription->items->map(fn ($item) => [
                'medicine_name' => $item->medicine_name,
                'dosage' => $item->dosage,
                'frequency' => $item->frequency,
                'duration' => $item->duration,
            ]),
        ]);
    }

    public function dispensingAlerts(Request $request)
    {
        $this->authPharmacistOrReceptionist($request);

        $map = fn ($d) => $this->drugPayload($d);

        $lowStock = Drug::where('status', 'active')->whereColumn('current_stock', '<=', 'min_stock_level')->get()->map($map);
        $nearExpiry = Drug::where('status', 'active')->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(30))->whereDate('expiry_date', '>=', now())->get()->map($map);
        $expired = Drug::where('status', 'active')->whereNotNull('expiry_date')->whereDate('expiry_date', '<', now())->get()->map($map);

        return response()->json(['low_stock' => $lowStock, 'near_expiry' => $nearExpiry, 'expired' => $expired]);
    }

    public function dispensingStore(Request $request, DispensingService $service)
    {
        $pharmacist = $this->authPharmacist($request);

        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'prescription_id' => ['nullable', 'exists:prescriptions,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.drug_id' => ['required', 'exists:drugs,id'],
            'items.*.quantity_dispensed' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.quantity_prescribed' => ['nullable', 'integer'],
        ]);

        $dispensing = $service->dispense($validated, $pharmacist->id);

        return response()->json(['message' => 'Dispensing completed!', 'id' => $dispensing->id], 201);
    }

    // ============================================================
    // Patients (picker for dispensing/order forms)
    // ============================================================

    public function patients(Request $request)
    {
        $this->authPharmacistOrReceptionist($request);

        $query = Patient::select(['id', 'mrn', 'name', 'phone'])->where('status', 'active');
        if ($request->filled('q')) {
            $keyword = $request->q;
            $hash = Patient::hashFor($keyword);
            $query->where(function ($q) use ($keyword, $hash) {
                $q->where('mrn', 'like', "{$keyword}%");
                if ($hash) $q->orWhere('name_hash', $hash);
            });
        }

        $patients = $query->latest()->take(50)->get()->map(fn (Patient $p) => ['id' => $p->id, 'mrn' => $p->mrn, 'name' => $p->name, 'phone' => $p->phone]);

        return response()->json(['patients' => $patients]);
    }

    // ============================================================
    // Profile (pharmacist)
    // ============================================================

    public function profile(Request $request)
    {
        $pharmacist = $this->authPharmacist($request);

        return response()->json($this->profilePayload($pharmacist));
    }

    public function updateProfile(Request $request)
    {
        $pharmacist = $this->authPharmacist($request);

        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($pharmacist->id)],
            'phone' => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($pharmacist->profile_photo) {
                Storage::disk('public')->delete($pharmacist->profile_photo);
            }
            $pharmacist->update(['profile_photo' => $request->file('profile_photo')->store('profile-photos', 'public')]);
        }

        $pharmacist->update(['name' => $request->name, 'email' => $request->email, 'phone' => $request->phone]);

        if ($request->filled('password')) {
            $pharmacist->update(['password' => Hash::make($request->password), 'must_change_password' => false]);
        }

        return response()->json($this->profilePayload($pharmacist->fresh()));
    }

    private function profilePayload(User $pharmacist): array
    {
        return [
            'user' => [
                'id' => $pharmacist->id,
                'name' => $pharmacist->name,
                'email' => $pharmacist->email,
                'phone' => $pharmacist->phone,
                'profile_photo_url' => $pharmacist->profile_photo_url,
                'department' => $pharmacist->department?->name,
            ],
        ];
    }
}
