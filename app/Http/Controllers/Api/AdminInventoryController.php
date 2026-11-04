<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BillingService;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Non-drug stock, the purchase orders that replenish it, and the billing
 * service catalogue — the three admin-only masters the mobile app had no
 * way to reach.
 *
 * Receiving a purchase order raises the stock of every line on it, inside
 * one transaction, exactly as the web does. Doing it any other way would
 * let a phone and a browser disagree about what is on the shelf.
 */
class AdminInventoryController extends Controller
{
    use AuthorizesRoles;

    private function authAdmin(Request $request): User
    {
        return $this->authRoles($request, [], 'This account is not an administrator account.');
    }

    // ----------------------------------------------------------- inventory

    public function items(Request $request)
    {
        $this->authAdmin($request);

        $items = InventoryItem::query()
            ->when($request->search, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('item_name', 'like', "%{$v}%")
                ->orWhere('item_code', 'like', "%{$v}%")))
            ->when($request->category, fn ($q, $v) => $q->where('category', $v))
            ->when($request->boolean('low_stock_only'), fn ($q) => $q->whereColumn('current_stock', '<=', 'min_stock_level'))
            ->orderBy('item_name')
            ->limit(400)
            ->get();

        return response()->json([
            'totals' => [
                'items' => $items->count(),
                'low_stock' => $items->filter(fn ($i) => (int) $i->current_stock <= (int) $i->min_stock_level)->count(),
                'stock_value' => round($items->sum(fn ($i) => (float) $i->unit_price * (int) $i->current_stock), 2),
            ],
            'categories' => InventoryItem::query()->distinct()->orderBy('category')->pluck('category')->filter()->values(),
            'items' => $items->map(fn ($i) => $this->itemPayload($i)),
        ]);
    }

    private function itemPayload(InventoryItem $i): array
    {
        return [
            'id' => $i->id,
            'item_code' => $i->item_code,
            'item_name' => $i->item_name,
            'category' => $i->category,
            'unit' => $i->unit,
            'current_stock' => (int) $i->current_stock,
            'min_stock_level' => (int) $i->min_stock_level,
            'unit_price' => (float) $i->unit_price,
            'supplier' => $i->supplier,
            'location' => $i->location,
            'status' => $i->status,
            'is_low_stock' => (int) $i->current_stock <= (int) $i->min_stock_level,
        ];
    }

    private function itemRules(?InventoryItem $item = null): array
    {
        return [
            'item_name' => ['required', 'string', 'min:2', 'max:255'],
            'item_code' => ['nullable', 'string', 'max:50', Rule::unique('inventory_items', 'item_code')->ignore($item?->id)],
            'category' => ['required', 'string', 'max:100'],
            // Required, matching the web and the column: inventory_items.unit
            // is NOT NULL with no default, so a nullable rule here turned a
            // missing unit into a 500 instead of a field error.
            'unit' => ['required', 'string', 'max:50'],
            'current_stock' => ['nullable', 'integer', 'min:0'],
            'min_stock_level' => ['nullable', 'integer', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }

    public function itemStore(Request $request)
    {
        $this->authAdmin($request);
        $validated = $request->validate($this->itemRules());

        $item = InventoryItem::create($validated + [
            'current_stock' => $validated['current_stock'] ?? 0,
            'min_stock_level' => $validated['min_stock_level'] ?? 0,
            'status' => $validated['status'] ?? 'active',
        ]);

        ActivityLog::log('created', 'Inventory', "Item {$item->item_name} added from the mobile app");

        return response()->json(['message' => 'Item added.', 'id' => $item->id], 201);
    }

    public function itemUpdate(Request $request, InventoryItem $item)
    {
        $this->authAdmin($request);
        $item->update($request->validate($this->itemRules($item)));

        return response()->json(['message' => 'Item updated.']);
    }

    public function itemDestroy(Request $request, InventoryItem $item)
    {
        $this->authAdmin($request);
        $name = $item->item_name;
        $item->delete();

        ActivityLog::log('deleted', 'Inventory', "Item {$name} removed from the mobile app");

        return response()->json(['message' => 'Item deleted.']);
    }

    // ----------------------------------------------------- purchase orders

    public function purchaseOrders(Request $request)
    {
        $this->authAdmin($request);

        $orders = PurchaseOrder::with('items')
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->limit(200)
            ->get();

        return response()->json([
            'totals' => [
                'orders' => $orders->count(),
                'draft' => $orders->where('status', 'draft')->count(),
                'received' => $orders->where('status', 'received')->count(),
                'value' => round((float) $orders->sum('total_amount'), 2),
            ],
            'orders' => $orders->map(fn ($po) => [
                'id' => $po->id,
                'po_no' => $po->po_no,
                'supplier' => $po->supplier,
                'status' => $po->status,
                'order_date' => $po->order_date,
                'expected_date' => $po->expected_date,
                'received_date' => $po->received_date,
                'total_amount' => (float) $po->total_amount,
                'item_count' => $po->items->count(),
            ]),
        ]);
    }

    public function purchaseOrderStore(Request $request)
    {
        $admin = $this->authAdmin($request);

        $validated = $request->validate([
            'supplier' => ['required', 'string', 'max:255'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $po = DB::transaction(function () use ($validated, $admin) {
            $total = collect($validated['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_price']);

            $po = PurchaseOrder::create([
                'supplier' => $validated['supplier'],
                'raised_by' => $admin->id,
                'total_amount' => $total,
                'status' => 'draft',
                'order_date' => now()->toDateString(),
                'expected_date' => $validated['expected_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $po;
        });

        return response()->json(['message' => 'Purchase order raised.', 'id' => $po->id, 'po_no' => $po->po_no], 201);
    }

    public function purchaseOrderReceive(Request $request, PurchaseOrder $po)
    {
        $this->authAdmin($request);
        abort_if($po->status === 'received', 422, 'Purchase order already received.');

        DB::transaction(function () use ($po) {
            foreach ($po->items as $line) {
                InventoryItem::where('id', $line->inventory_item_id)->increment('current_stock', (int) $line->quantity);
            }

            $po->update(['status' => 'received', 'received_date' => now()->toDateString()]);
        });

        ActivityLog::log('updated', 'Inventory', "Purchase order {$po->po_no} received from the mobile app");

        return response()->json(['message' => 'Purchase order received — stock updated.']);
    }

    public function purchaseOrderDestroy(Request $request, PurchaseOrder $po)
    {
        $this->authAdmin($request);
        abort_if($po->status === 'received', 422, 'A received purchase order cannot be deleted.');

        $no = $po->po_no;
        DB::transaction(function () use ($po) {
            $po->items()->delete();
            $po->delete();
        });

        ActivityLog::log('deleted', 'Inventory', "Purchase order {$no} deleted from the mobile app");

        return response()->json(['message' => 'Purchase order deleted.']);
    }

    // ---------------------------------------------------- billing services

    public function services(Request $request)
    {
        $this->authAdmin($request);

        $services = BillingService::query()
            ->when($request->search, fn ($q, $v) => $q->where('service_name', 'like', "%{$v}%"))
            ->when($request->category, fn ($q, $v) => $q->where('category', $v))
            ->orderBy('service_name')
            ->limit(400)
            ->get();

        return response()->json([
            'categories' => BillingService::query()->distinct()->orderBy('category')->pluck('category')->filter()->values(),
            'services' => $services->map(fn ($s) => [
                'id' => $s->id,
                'service_code' => $s->service_code,
                'service_name' => $s->service_name,
                'category' => $s->category,
                'price' => (float) $s->price,
                'description' => $s->description,
                'status' => $s->status,
            ]),
        ]);
    }

    private function serviceRules(?BillingService $service = null): array
    {
        return [
            'service_name' => ['required', 'string', 'min:2', 'max:255'],
            'service_code' => ['nullable', 'string', 'max:50', Rule::unique('billing_services', 'service_code')->ignore($service?->id)],
            'category' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }

    public function serviceStore(Request $request)
    {
        $this->authAdmin($request);
        $validated = $request->validate($this->serviceRules());

        $service = BillingService::create($validated + ['status' => $validated['status'] ?? 'active']);

        return response()->json(['message' => 'Service added.', 'id' => $service->id], 201);
    }

    public function serviceUpdate(Request $request, BillingService $billingService)
    {
        $this->authAdmin($request);
        $billingService->update($request->validate($this->serviceRules($billingService)));

        return response()->json(['message' => 'Service updated.']);
    }

    public function serviceDestroy(Request $request, BillingService $billingService)
    {
        $this->authAdmin($request);
        $billingService->delete();

        return response()->json(['message' => 'Service deleted.']);
    }
}
