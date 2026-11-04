<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Drug;
use App\Models\HospitalSetting;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The administrator's own endpoints: the numbers, the hospital's
 * settings, its departments, and the activity log.
 *
 * Everything else an admin does in the app — patients, wards, billing,
 * pharmacy, the lab — runs on the endpoints the clinical roles already
 * use; AuthorizesRoles lets an admin through those. Only what has no
 * clinical owner lives here.
 *
 * The figures mirror the web's ReportController@kpis rather than being
 * invented, so the dashboard an admin sees on a phone agrees with the one
 * on their desk.
 */
class AdminController extends Controller
{
    use AuthorizesRoles;

    private function authAdmin(Request $request): User
    {
        return $this->authRoles($request, [], 'This account is not an administrator account.');
    }

    // ---------------------------------------------------------------- kpis

    public function dashboard(Request $request)
    {
        $this->authAdmin($request);

        $lowStock = (int) config('hospital.low_stock', 10);
        $nearExpiry = (int) config('hospital.near_expiry', 30);

        return response()->json([
            'patients' => [
                'total' => Patient::count(),
                'active' => Patient::where('status', 'active')->count(),
                'op' => Patient::where('patient_type', 'op')->count(),
                'ip' => Patient::where('patient_type', 'ip')->count(),
            ],
            'appointments' => [
                'total' => Appointment::count(),
                'today' => Appointment::whereDate('appointment_date', today())->count(),
                'scheduled' => Appointment::where('status', 'scheduled')->count(),
                'completed' => Appointment::where('status', 'completed')->count(),
                'cancelled' => Appointment::where('status', 'cancelled')->count(),
            ],
            'revenue' => [
                'total' => round((float) Invoice::where('status', 'paid')->sum('total'), 2),
                'today' => round((float) Invoice::where('status', 'paid')->whereDate('created_at', today())->sum('total'), 2),
                'outstanding' => round((float) Invoice::where('status', 'unpaid')->sum('balance'), 2),
                'pending_bills' => Invoice::where('status', 'unpaid')->count(),
            ],
            'capacity' => [
                'doctors' => Doctor::count(),
                'available_beds' => (int) Ward::sum('available_beds'),
                'lab_orders' => LabOrder::count(),
                'pending_lab_orders' => LabOrder::where('status', 'pending')->count(),
                'low_stock_drugs' => Drug::where('current_stock', '<=', $lowStock)->count(),
                'near_expiry_drugs' => Drug::whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '>=', now())
                    ->whereDate('expiry_date', '<=', now()->addDays($nearExpiry))
                    ->count(),
            ],
            // Six months back, oldest first — the shape a chart wants.
            'monthly' => collect(range(5, 0))->map(function (int $back) {
                $month = now()->subMonths($back);

                return [
                    'month' => $month->format('M Y'),
                    'patients' => Patient::whereYear('created_at', $month->year)
                        ->whereMonth('created_at', $month->month)->count(),
                    'revenue' => round((float) Invoice::where('status', 'paid')
                        ->whereYear('created_at', $month->year)
                        ->whereMonth('created_at', $month->month)
                        ->sum('total'), 2),
                ];
            })->values(),
        ]);
    }

    // ------------------------------------------------------------- reports

    public function patientReport(Request $request)
    {
        $this->authAdmin($request);

        $patients = Patient::query()
            ->when($request->from_date, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->to_date, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($request->patient_type, fn ($q, $v) => $q->where('patient_type', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->limit(500)
            ->get();

        return response()->json([
            'total' => $patients->count(),
            'by_type' => $patients->groupBy('patient_type')->map->count(),
            'patients' => $patients->map(fn ($p) => [
                'id' => $p->id,
                'mrn' => $p->mrn,
                'name' => $p->name,
                'gender' => $p->gender,
                'patient_type' => $p->patient_type,
                'status' => $p->status,
                'registered_on' => $p->created_at?->toDateString(),
            ]),
        ]);
    }

    public function billingReport(Request $request)
    {
        $this->authAdmin($request);

        $invoices = Invoice::with('patient')
            ->when($request->from_date, fn ($q, $v) => $q->whereDate('invoice_date', '>=', $v))
            ->when($request->to_date, fn ($q, $v) => $q->whereDate('invoice_date', '<=', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->limit(500)
            ->get();

        return response()->json([
            'totals' => [
                'amount' => round((float) $invoices->sum('total'), 2),
                'paid' => round((float) $invoices->sum('paid_amount'), 2),
                'balance' => round((float) $invoices->sum('balance'), 2),
                'count' => $invoices->count(),
            ],
            'invoices' => $invoices->map(fn ($i) => [
                'id' => $i->id,
                'invoice_no' => $i->invoice_no,
                'patient_name' => $i->patient?->name,
                // Cast to a Carbon date on the model, so without this it
                // serialises as a full ISO timestamp and the app shows
                // "2026-09-19T00:00:00.000000Z" where a date belongs.
                'invoice_date' => $i->invoice_date instanceof \DateTimeInterface
                    ? $i->invoice_date->format('Y-m-d')
                    : $i->invoice_date,
                'status' => $i->status,
                'total' => (float) $i->total,
                'paid_amount' => (float) $i->paid_amount,
                'balance' => (float) $i->balance,
            ]),
        ]);
    }

    public function inventoryReport(Request $request)
    {
        $this->authAdmin($request);

        $lowStock = (int) config('hospital.low_stock', 10);
        $drugs = Drug::query()
            ->when($request->boolean('low_stock_only'), fn ($q) => $q->where('current_stock', '<=', $lowStock))
            ->orderBy('drug_name')
            ->limit(500)
            ->get();

        return response()->json([
            'totals' => [
                'drugs' => $drugs->count(),
                'stock_value' => round((float) $drugs->sum(fn ($d) => (float) $d->unit_price * (int) $d->current_stock), 2),
                'low_stock' => $drugs->where('current_stock', '<=', $lowStock)->count(),
            ],
            'drugs' => $drugs->map(fn ($d) => [
                'id' => $d->id,
                'drug_name' => $d->drug_name,
                'category' => $d->category,
                'current_stock' => (int) $d->current_stock,
                'min_stock_level' => (int) $d->min_stock_level,
                'unit_price' => (float) $d->unit_price,
                'expiry_date' => $d->expiry_date instanceof \DateTimeInterface
                    ? $d->expiry_date->format('Y-m-d')
                    : $d->expiry_date,
            ]),
        ]);
    }

    // ------------------------------------------------------------ settings

    public function settings(Request $request)
    {
        $this->authAdmin($request);
        $settings = HospitalSetting::first();

        return response()->json(['settings' => $settings]);
    }

    public function updateSettings(Request $request)
    {
        $this->authAdmin($request);

        $validated = $request->validate([
            'hospital_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'near_expiry_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'round_alert_hours' => ['nullable', 'integer', 'min:1', 'max:72'],
        ]);

        // A hospital starts with no settings row — the web's own form
        // creates one on first save rather than failing, and an admin on a
        // phone should not be the one who hits a 404 for that.
        $settings = HospitalSetting::first();
        if ($settings === null) {
            $settings = HospitalSetting::create($validated);
        } else {
            $settings->update($validated);
        }

        ActivityLog::log('updated', 'Settings', 'Hospital settings updated from the mobile app');

        return response()->json(['message' => 'Settings saved.', 'settings' => $settings->fresh()]);
    }

    // --------------------------------------------------------- departments

    public function departments(Request $request)
    {
        $this->authAdmin($request);

        return response()->json([
            'departments' => Department::orderBy('name')->get()->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'description' => $d->description,
                'status' => $d->status,
            ]),
        ]);
    }

    public function departmentStore(Request $request)
    {
        $this->authAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $department = Department::create($validated + ['status' => $validated['status'] ?? 'active']);

        return response()->json(['message' => 'Department added.', 'id' => $department->id], 201);
    }

    public function departmentUpdate(Request $request, Department $department)
    {
        $this->authAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department->id)],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $department->update($validated);

        return response()->json(['message' => 'Department updated.']);
    }

    public function departmentDestroy(Request $request, Department $department)
    {
        $this->authAdmin($request);
        $department->delete();

        return response()->json(['message' => 'Department deleted.']);
    }

    // -------------------------------------------------------- activity log

    public function activityLog(Request $request)
    {
        $this->authAdmin($request);

        $logs = ActivityLog::query()
            ->when($request->module, fn ($q, $v) => $q->where('module', $v))
            ->when($request->action, fn ($q, $v) => $q->where('action', $v))
            ->when($request->search, fn ($q, $v) => $q->where('description', 'like', "%{$v}%"))
            ->latest()
            ->limit(200)
            ->get();

        return response()->json([
            'modules' => ActivityLog::query()->distinct()->orderBy('module')->pluck('module')->filter()->values(),
            'logs' => $logs->map(fn ($l) => [
                'id' => $l->id,
                'user_name' => $l->user_name,
                'action' => $l->action,
                'module' => $l->module,
                'description' => $l->description,
                'ip_address' => $l->ip_address,
                'at' => $l->created_at?->toDateTimeString(),
            ]),
        ]);
    }
}
