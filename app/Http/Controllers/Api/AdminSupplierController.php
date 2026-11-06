<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The vendor master behind purchase orders.
 *
 * Orders store the supplier as text, which is how they were written, so
 * this list exists to keep those names consistent and to hold the
 * contact details that were previously nowhere. Each row carries what
 * has been ordered from that supplier, matched on the name.
 */
class AdminSupplierController extends Controller
{
    use AuthorizesRoles;

    private function authAdmin(Request $request)
    {
        return $this->authRoles($request, [], 'This account cannot manage suppliers.');
    }

    public function index(Request $request)
    {
        $this->authAdmin($request);

        $suppliers = Supplier::orderBy('name')->get();

        // One grouped query rather than a count per supplier.
        $orders = PurchaseOrder::selectRaw('supplier, COUNT(*) as orders, SUM(total_amount) as spend')
            ->groupBy('supplier')
            ->pluck('orders', 'supplier');
        $spend = PurchaseOrder::selectRaw('supplier, SUM(total_amount) as spend')
            ->groupBy('supplier')
            ->pluck('spend', 'supplier');

        return response()->json([
            'stats' => [
                'suppliers' => $suppliers->count(),
                'active' => $suppliers->where('status', 'active')->count(),
            ],
            'suppliers' => $suppliers->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'contact_person' => $s->contact_person,
                'phone' => $s->phone,
                'email' => $s->email,
                'address' => $s->address,
                'gst_no' => $s->gst_no,
                'payment_terms' => $s->payment_terms,
                'status' => $s->status,
                'notes' => $s->notes,
                'orders' => (int) ($orders[$s->name] ?? 0),
                'spend' => (float) ($spend[$s->name] ?? 0),
            ])->all(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authAdmin($request);

        $supplier = Supplier::create($this->rules($request));

        return response()->json(['message' => 'Supplier added.', 'id' => $supplier->id], 201);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->authAdmin($request);

        $validated = $this->rules($request, $supplier);
        $oldName = $supplier->name;

        $supplier->update($validated);

        // Orders point at the name, so renaming has to carry them along
        // or their history detaches from the supplier.
        if ($oldName !== $supplier->name) {
            PurchaseOrder::where('supplier', $oldName)->update(['supplier' => $supplier->name]);
        }

        return response()->json(['message' => 'Supplier updated.']);
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        $this->authAdmin($request);

        $orders = PurchaseOrder::where('supplier', $supplier->name)->count();
        abort_if($orders > 0, 422, "This supplier has {$orders} purchase order(s). Mark it inactive instead.");

        $supplier->delete();

        return response()->json(['message' => 'Supplier removed.']);
    }

    private function rules(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255', Rule::unique('suppliers', 'name')->ignore($supplier?->id)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'gst_no' => ['nullable', 'string', 'max:30'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
