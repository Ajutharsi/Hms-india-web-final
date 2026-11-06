<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Wards and beds, for administrators.
 *
 * The clinical roles could already read wards (NurseController::wards)
 * and admit into them; nobody could create one, rename it, change what a
 * night costs, or add a bed. That was a web-only page.
 *
 * Creating a ward also creates its beds -- the Ward model has done that
 * since the beginning -- so the app does not ask for both.
 */
class AdminWardController extends Controller
{
    use AuthorizesRoles;

    private function authAdmin(Request $request)
    {
        return $this->authRoles($request, [], 'This account cannot manage wards.');
    }

    public function index(Request $request)
    {
        $this->authAdmin($request);

        $wards = Ward::withCount([
            'beds',
            'beds as occupied_beds_count' => fn ($q) => $q->where('status', 'occupied'),
        ])->orderBy('name')->get();

        return response()->json([
            'stats' => [
                'wards' => $wards->count(),
                'beds' => (int) $wards->sum('beds_count'),
                'occupied' => (int) $wards->sum('occupied_beds_count'),
                'free' => (int) $wards->sum(fn ($w) => $w->beds_count - $w->occupied_beds_count),
            ],
            'wards' => $wards->map(fn ($w) => [
                'id' => $w->id,
                'ward_no' => $w->ward_no,
                'name' => $w->name,
                'type' => $w->type,
                'floor' => $w->floor,
                'building' => $w->building,
                'total_beds' => (int) $w->beds_count,
                'occupied_beds' => (int) $w->occupied_beds_count,
                'available_beds' => (int) ($w->beds_count - $w->occupied_beds_count),
                'charge_per_day' => (float) $w->charge_per_day,
                'status' => $w->status,
                'description' => $w->description,
            ])->all(),
        ]);
    }

    public function show(Request $request, Ward $ward)
    {
        $this->authAdmin($request);

        $beds = $ward->beds()->with('currentAdmission.patient')->orderBy('bed_no')->get();

        return response()->json([
            'ward' => [
                'id' => $ward->id,
                'ward_no' => $ward->ward_no,
                'name' => $ward->name,
                'type' => $ward->type,
                'floor' => $ward->floor,
                'building' => $ward->building,
                'charge_per_day' => (float) $ward->charge_per_day,
                'status' => $ward->status,
                'description' => $ward->description,
            ],
            'beds' => $beds->map(fn ($b) => [
                'id' => $b->id,
                'bed_no' => $b->bed_no,
                'type' => $b->type,
                'status' => $b->status,
                'notes' => $b->notes,
                'patient_name' => $b->currentAdmission?->patient?->name,
            ])->all(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['required', 'in:general,private,semi_private,icu,nicu,emergency'],
            'floor' => ['nullable', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'total_beds' => ['required', 'integer', 'min:1', 'max:200'],
            'charge_per_day' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        // The model's created() hook makes one bed per total_beds.
        $ward = Ward::create($validated + ['available_beds' => $validated['total_beds']]);

        return response()->json([
            'message' => "Ward added with {$validated['total_beds']} beds.",
            'id' => $ward->id,
        ], 201);
    }

    public function update(Request $request, Ward $ward)
    {
        $this->authAdmin($request);

        // total_beds is deliberately not editable here: beds are added
        // and removed one at a time below, so the count stays something
        // the bed rows actually back.
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['required', 'in:general,private,semi_private,icu,nicu,emergency'],
            'floor' => ['nullable', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'charge_per_day' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $ward->update($validated);

        return response()->json(['message' => 'Ward updated.']);
    }

    public function destroy(Request $request, Ward $ward)
    {
        $this->authAdmin($request);

        // Deleting a ward cascades to its beds, which would take an
        // occupied bed out from under an admitted patient.
        $occupied = $ward->beds()->where('status', 'occupied')->count();
        abort_if($occupied > 0, 422, "This ward still has {$occupied} occupied bed(s).");

        $ward->delete();

        return response()->json(['message' => 'Ward removed.']);
    }

    public function storeBed(Request $request, Ward $ward)
    {
        $this->authAdmin($request);

        $validated = $request->validate([
            'bed_no' => ['nullable', 'string', 'max:50', Rule::unique('beds', 'bed_no')],
            'type' => ['required', 'in:standard,electric,icu,nicu'],
            'status' => ['nullable', 'in:available,maintenance,reserved'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $bed = $ward->beds()->create([
            'bed_no' => $validated['bed_no'] ?? $this->nextBedNo($ward),
            'type' => $validated['type'],
            'status' => $validated['status'] ?? 'available',
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->syncCounts($ward);

        return response()->json(['message' => "Bed {$bed->bed_no} added.", 'id' => $bed->id], 201);
    }

    public function updateBed(Request $request, Bed $bed)
    {
        $this->authAdmin($request);

        $validated = $request->validate([
            'type' => ['required', 'in:standard,electric,icu,nicu'],
            // 'occupied' is not settable by hand: admitting and
            // discharging is what moves a bed in and out of use.
            'status' => ['required', 'in:available,maintenance,reserved'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_if($bed->status === 'occupied', 422, 'This bed has a patient in it.');

        $bed->update($validated);
        $this->syncCounts($bed->ward);

        return response()->json(['message' => 'Bed updated.']);
    }

    public function destroyBed(Request $request, Bed $bed)
    {
        $this->authAdmin($request);

        abort_if($bed->status === 'occupied', 422, 'This bed has a patient in it.');

        $ward = $bed->ward;
        $bed->delete();
        $this->syncCounts($ward);

        return response()->json(['message' => 'Bed removed.']);
    }

    /**
     * Keep the ward's own counters equal to the beds that exist.
     *
     * They are stored on the ward and read all over the app, and the
     * seeded rows had already drifted (a ward claiming 20 beds with 0
     * free while none were occupied).
     */
    private function syncCounts(?Ward $ward): void
    {
        if ($ward === null) {
            return;
        }

        $total = $ward->beds()->count();
        $occupied = $ward->beds()->where('status', 'occupied')->count();

        $ward->update([
            'total_beds' => $total,
            'available_beds' => $total - $occupied,
        ]);
    }

    private function nextBedNo(Ward $ward): string
    {
        $used = $ward->beds()->pluck('bed_no');

        for ($i = 1; $i <= 999; $i++) {
            $candidate = $ward->ward_no . '-B' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            if (! $used->contains($candidate)) {
                return $candidate;
            }
        }

        return $ward->ward_no . '-B' . now()->format('His');
    }
}
