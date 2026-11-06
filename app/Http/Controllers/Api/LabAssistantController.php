<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\PatientMedicalRecord;
use App\Models\User;
use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * JSON endpoints for the Flutter lab-assistant app — mirrors the web's
 * LabDashboardController + Lab\LabTestController + Lab\LabOrderController.
 * Same pattern as NurseController: authLabAssistant() just checks the role,
 * there's no separate LabAssistant domain model.
 */
class LabAssistantController extends Controller
{
    use AuthorizesRoles;

    private function authLabAssistant(Request $request): User
    {
        return $this->authRoles($request, ['lab_assistant'], 'This account is not a lab assistant account.');
    }

    /**
     * Entering a result against an order. The web allows the ordering
     * doctor as well as the lab (`role:...|lab_assistant|doctor`), and the
     * app already showed them the button: the doctor's drawer opens the
     * lab's own order screen, whose detail view carries "Enter results".
     * Only the request behind it was refused, so the button was there and
     * did nothing.
     *
     * The receptionist stays out, as on the web — they can raise an order
     * and read one, but not write a clinical result.
     */
    private function authResultsEntry(Request $request): User
    {
        return $this->authRoles($request, ['lab_assistant', 'doctor'], 'This account cannot enter lab results.');
    }

    /**
     * Lab Tests catalog CRUD and Lab Orders list/create/detail, shared on
     * the web between the lab assistant, the receptionist and the doctor
     * (`role:...|lab_assistant|doctor|receptionist`) — the doctor is the
     * one ordering the test in the first place.
     *
     * Entering results stays on [authLabAssistant]: the web excludes the
     * receptionist from clinical data entry, and a result belongs to
     * whoever ran the sample.
     */
    private function authLabDesk(Request $request): User
    {
        return $this->authRoles($request, ['lab_assistant', 'receptionist', 'doctor']);
    }

    public function dashboard(Request $request)
    {
        $this->authLabAssistant($request);

        return response()->json([
            'stats' => [
                'pending_orders' => LabOrder::where('status', 'pending')->count(),
                'completed_today' => LabOrder::where('status', 'completed')->whereDate('updated_at', today())->count(),
                'total_orders' => LabOrder::count(),
                'lab_tests' => LabTest::count(),
            ],
            'today_appointments' => [],
            'recent_patients' => [],
        ]);
    }

    // ============================================================
    // Lab Tests (catalog)
    // ============================================================

    public function labTests(Request $request)
    {
        $this->authLabDesk($request);

        $tests = LabTest::orderBy('test_name')->get()->map(fn (LabTest $t) => $this->labTestPayload($t));

        return response()->json(['lab_tests' => $tests]);
    }

    private function labTestPayload(LabTest $t): array
    {
        return [
            'id' => $t->id,
            'test_code' => $t->test_code,
            'test_name' => $t->test_name,
            'category' => $t->category,
            'sample_type' => $t->sample_type,
            'price' => (float) $t->price,
            'unit' => $t->unit,
            'normal_range_male' => $t->normal_range_male,
            'normal_range_female' => $t->normal_range_female,
            'description' => $t->description,
            'status' => $t->status,
        ];
    }

    public function labTestStore(Request $request)
    {
        $this->authLabDesk($request);

        $validated = $request->validate([
            'test_name' => ['required', 'string', 'min:2', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'sample_type' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:255'],
            'normal_range_male' => ['nullable', 'string', 'max:255'],
            'normal_range_female' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $test = LabTest::create($validated);

        return response()->json(['message' => 'Lab test added!', 'lab_test' => $this->labTestPayload($test)], 201);
    }

    public function labTestUpdate(Request $request, LabTest $labTest)
    {
        $this->authLabDesk($request);

        $validated = $request->validate([
            'test_name' => ['required', 'string', 'min:2', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'sample_type' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:255'],
            'normal_range_male' => ['nullable', 'string', 'max:255'],
            'normal_range_female' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $labTest->update($validated);

        return response()->json(['message' => 'Lab test updated!', 'lab_test' => $this->labTestPayload($labTest)]);
    }

    public function labTestDestroy(Request $request, LabTest $labTest)
    {
        $this->authLabDesk($request);
        $labTest->delete();

        return response()->json(['message' => 'Lab test deleted.']);
    }

    // ============================================================
    // Lab Orders
    // ============================================================

    public function labOrders(Request $request)
    {
        $this->authLabDesk($request);

        $orders = LabOrder::with(['patient:id,name,mrn', 'doctor:id,name'])
            ->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->take(150)
            ->get()
            ->map(fn (LabOrder $o) => [
                'id' => $o->id,
                'order_no' => $o->order_no,
                'patient_id' => $o->patient_id,
                'patient_name' => $o->patient?->name,
                'patient_mrn' => $o->patient?->mrn,
                'doctor_name' => $o->doctor?->name,
                'order_date' => $o->order_date,
                'status' => $o->status,
                'notes' => $o->notes,
                'tests_count' => $o->items_count,
                'sample_barcode' => $o->sample_barcode,
                'sample_type' => $o->sample_type,
                'sample_collected_at' => optional($o->sample_collected_at)->format('Y-m-d H:i'),
            ]);

        return response()->json(['lab_orders' => $orders]);
    }

    public function labOrderStore(Request $request)
    {
        $this->authLabDesk($request);

        $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'tests' => ['required', 'array', 'min:1'],
            'tests.*' => ['exists:lab_tests,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = LabOrder::create([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'appointment_id' => $request->appointment_id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        foreach ($request->tests as $testId) {
            LabOrderItem::create(['lab_order_id' => $order->id, 'lab_test_id' => $testId, 'status' => 'pending']);
        }

        PatientMedicalRecord::create([
            'patient_id' => $request->patient_id,
            'record_type' => 'Lab Result',
            'record_date' => now()->toDateString(),
            'description' => 'Lab Order ' . $order->order_no . ' created',
            'doctor_name' => Doctor::find($request->doctor_id)->name ?? null,
            'notes' => $request->notes,
            'reference_id' => $order->id,
            'reference_type' => 'lab_order',
        ]);

        return response()->json(['message' => 'Lab order created!', 'id' => $order->id], 201);
    }

    public function labOrderDetail(Request $request, LabOrder $labOrder)
    {
        $this->authLabDesk($request);
        $labOrder->load(['patient', 'doctor', 'items.labTest', 'items.result']);

        return response()->json([
            'id' => $labOrder->id,
            'order_no' => $labOrder->order_no,
            'patient_id' => $labOrder->patient_id,
            'patient_name' => $labOrder->patient?->name,
            'patient_mrn' => $labOrder->patient?->mrn,
            'doctor_name' => $labOrder->doctor?->name,
            'order_date' => $labOrder->order_date,
            'status' => $labOrder->status,
            'notes' => $labOrder->notes,
            'items' => $labOrder->items->map(fn (LabOrderItem $item) => [
                'id' => $item->id,
                'test_name' => $item->labTest?->test_name,
                'category' => $item->labTest?->category,
                'sample_type' => $item->labTest?->sample_type,
                'unit' => $item->labTest?->unit,
                'normal_range_male' => $item->labTest?->normal_range_male,
                'normal_range_female' => $item->labTest?->normal_range_female,
                'status' => $item->status,
                'result' => $item->result ? [
                    'result_value' => $item->result->result_value,
                    'unit' => $item->result->unit,
                    'normal_range' => $item->result->normal_range,
                    'flag' => $item->result->flag,
                    'remarks' => $item->result->remarks,
                    'result_date' => $item->result->result_date,
                ] : null,
            ]),
        ]);
    }

    /**
     * Mirrors LabOrderController::enterResults — same flag calculation
     * (normal_range_male parsed as "min-max", value outside → high/low).
     */
    public function labOrderEnterResults(Request $request, LabOrder $labOrder)
    {
        $enteredBy = $this->authResultsEntry($request);

        $request->validate([
            'results' => ['required', 'array'],
            'results.*.item_id' => ['required', 'integer'],
            'results.*.result_value' => ['required', 'string', 'max:255'],
            'results.*.unit' => ['nullable', 'string', 'max:255'],
            'results.*.normal_range' => ['nullable', 'string', 'max:255'],
            'results.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $items = LabOrderItem::with('labTest')
            ->whereIn('id', collect($request->results)->pluck('item_id'))
            ->get()
            ->keyBy('id');

        foreach ($request->results as $data) {
            $item = $items->get($data['item_id']);
            if (!$item) continue;

            $flag = 'normal';
            $test = $item->labTest;
            if ($test && $test->normal_range_male && str_contains($test->normal_range_male, '-')) {
                [$min, $max] = explode('-', $test->normal_range_male);
                $value = (float) $data['result_value'];
                if ($value < (float) $min) $flag = 'low';
                elseif ($value > (float) $max) $flag = 'high';
            }

            LabResult::updateOrCreate(
                ['lab_order_item_id' => $item->id],
                [
                    'lab_order_id' => $labOrder->id,
                    'patient_id' => $labOrder->patient_id,
                    'lab_test_id' => $item->lab_test_id,
                    'result_value' => $data['result_value'],
                    'unit' => $data['unit'] ?? $test->unit ?? null,
                    'normal_range' => $data['normal_range'] ?? null,
                    'flag' => $flag,
                    'remarks' => $data['remarks'] ?? null,
                    'performed_by' => $enteredBy->id,
                    'result_date' => now(),
                ]
            );

            $item->update(['status' => 'completed']);
        }

        if ($labOrder->items()->where('status', 'pending')->count() === 0) {
            $labOrder->update(['status' => 'completed']);
        }

        return response()->json(['message' => 'Results saved!']);
    }

    // ============================================================
    // Patients (picker for creating an order — same MRN/name-hash search
    // pattern as NurseController::patients)
    // ============================================================

    public function patients(Request $request)
    {
        $this->authLabAssistant($request);

        $query = Patient::select(['id', 'mrn', 'name', 'phone', 'status'])->where('status', 'active');

        if ($request->filled('q')) {
            $keyword = $request->q;
            $hash = Patient::hashFor($keyword);
            $query->where(function ($q) use ($keyword, $hash) {
                $q->where('mrn', 'like', "{$keyword}%");
                if ($hash) $q->orWhere('name_hash', $hash);
            });
        }

        $patients = $query->latest()->take(50)->get()->map(fn (Patient $p) => [
            'id' => $p->id,
            'mrn' => $p->mrn,
            'name' => $p->name,
            'phone' => $p->phone,
        ]);

        return response()->json(['patients' => $patients]);
    }

    // ============================================================
    // Profile
    // ============================================================

    public function profile(Request $request)
    {
        $labAssistant = $this->authLabAssistant($request);

        return response()->json($this->profilePayload($labAssistant));
    }

    public function updateProfile(Request $request)
    {
        $labAssistant = $this->authLabAssistant($request);

        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($labAssistant->id)],
            'phone' => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($labAssistant->profile_photo) {
                Storage::disk('public')->delete($labAssistant->profile_photo);
            }
            $labAssistant->update(['profile_photo' => $request->file('profile_photo')->store('profile-photos', 'public')]);
        }

        $labAssistant->update(['name' => $request->name, 'email' => $request->email, 'phone' => $request->phone]);

        if ($request->filled('password')) {
            $labAssistant->update(['password' => Hash::make($request->password), 'must_change_password' => false]);
        }

        return response()->json($this->profilePayload($labAssistant->fresh()));
    }

    private function profilePayload(User $labAssistant): array
    {
        return [
            'user' => [
                'id' => $labAssistant->id,
                'name' => $labAssistant->name,
                'email' => $labAssistant->email,
                'phone' => $labAssistant->phone,
                'profile_photo_url' => $labAssistant->profile_photo_url,
                'department' => $labAssistant->department?->name,
            ],
        ];
    }

    // -------------------------------------------------- sample collection

    /**
     * Orders waiting for a sample to be taken.
     *
     * An order used to go straight from "pending" to a typed-in result,
     * so nothing told the phlebotomist which tubes still had to be
     * drawn, and a mislabelled tube had nothing to trace.
     */
    public function samples(Request $request)
    {
        $this->authLabDesk($request);

        $orders = LabOrder::with(['patient:id,name,mrn', 'sampleCollectedBy:id,name'])
            ->withCount('items')
            ->when($request->get('state') === 'collected', fn ($q) => $q->whereNotNull('sample_collected_at'))
            ->when($request->get('state', 'pending') === 'pending', fn ($q) => $q->whereNull('sample_collected_at'))
            ->whereNotIn('status', ['cancelled'])
            ->latest('order_date')
            ->take(150)
            ->get();

        $waiting = LabOrder::whereNull('sample_collected_at')->whereNotIn('status', ['cancelled'])->count();

        return response()->json([
            'stats' => [
                'waiting' => $waiting,
                'collected_today' => LabOrder::whereDate('sample_collected_at', now()->toDateString())->count(),
            ],
            'orders' => $orders->map(fn (LabOrder $o) => [
                'id' => $o->id,
                'order_no' => $o->order_no,
                'patient_name' => $o->patient?->name,
                'patient_mrn' => $o->patient?->mrn,
                'order_date' => $o->order_date,
                'tests_count' => $o->items_count,
                'status' => $o->status,
                'sample_barcode' => $o->sample_barcode,
                'sample_type' => $o->sample_type,
                'sample_collected_at' => optional($o->sample_collected_at)->format('Y-m-d H:i'),
                'sample_collected_by' => $o->sampleCollectedBy?->name,
                'sample_notes' => $o->sample_notes,
            ])->all(),
        ]);
    }

    public function collectSample(Request $request, LabOrder $labOrder)
    {
        $user = $this->authLabDesk($request);

        $validated = $request->validate([
            'sample_type' => ['required', 'string', 'max:60'],
            'sample_barcode' => ['nullable', 'string', 'max:40', Rule::unique('lab_orders', 'sample_barcode')->ignore($labOrder->id)],
            'sample_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_if($labOrder->sample_collected_at !== null, 422, 'A sample for this order was already collected.');

        $labOrder->update([
            'sample_type' => $validated['sample_type'],
            // The barcode goes on the tube; if the label printer gives
            // one, it is used, otherwise the order number is the label.
            'sample_barcode' => $validated['sample_barcode'] ?? $this->barcodeFor($labOrder),
            'sample_notes' => $validated['sample_notes'] ?? null,
            'sample_collected_at' => now(),
            'sample_collected_by' => $user->id,
            // Collected means the lab now has work to do on it.
            'status' => $labOrder->status === 'pending' ? 'processing' : $labOrder->status,
        ]);

        return response()->json([
            'message' => 'Sample collected.',
            'sample_barcode' => $labOrder->fresh()->sample_barcode,
        ]);
    }

    /** Find an order by the barcode on the tube. */
    public function sampleByBarcode(Request $request)
    {
        $this->authLabDesk($request);

        $barcode = trim((string) $request->get('barcode', ''));
        abort_if($barcode === '', 422, 'Scan or type a barcode.');

        $order = LabOrder::with(['patient:id,name,mrn', 'items.labTest'])
            ->where('sample_barcode', $barcode)
            ->first();

        abort_if($order === null, 404, 'No sample with that barcode.');

        return response()->json([
            'id' => $order->id,
            'order_no' => $order->order_no,
            'patient_name' => $order->patient?->name,
            'patient_mrn' => $order->patient?->mrn,
            'status' => $order->status,
            'sample_type' => $order->sample_type,
            'sample_collected_at' => optional($order->sample_collected_at)->format('Y-m-d H:i'),
            'tests' => $order->items->map(fn ($i) => $i->labTest?->name)->filter()->values()->all(),
        ]);
    }

    private function barcodeFor(LabOrder $order): string
    {
        return 'S-' . str_replace(['LAB-', '/'], '', (string) $order->order_no);
    }
}
