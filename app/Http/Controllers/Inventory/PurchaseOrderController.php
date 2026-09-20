<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $items = InventoryItem::where('status', 'active')->orderBy('item_name')->get(['id', 'item_name', 'unit_price', 'unit']);
        return view('inventory.purchase-orders.index', compact('items'));
    }

    public function data()
    {
        $query = PurchaseOrder::withCount('items')->latest();

        return DataTables::of($query)
            ->addColumn('total_amount_fmt', fn($po) => config('hospital.currency') . ' ' . number_format($po->total_amount, 2))
            ->addColumn('status_badge', function ($po) {
                $colors = ['draft' => 'secondary', 'submitted' => 'info', 'approved' => 'primary', 'received' => 'success', 'cancelled' => 'danger'];
                return '<span class="badge bg-label-' . ($colors[$po->status] ?? 'secondary') . '">' . $po->status . '</span>';
            })
            ->editColumn('order_date', fn($po) => $po->order_date?->format('d M Y'))
            ->editColumn('expected_date', fn($po) => $po->expected_date?->format('d M Y'))
            ->addColumn('actions', fn($po) => '
                <div class="d-flex gap-2">
                    ' . ($po->status !== 'received' && $po->status !== 'cancelled' ? '<button onclick="receivePo(' . $po->id . ')" class="btn btn-sm btn-icon btn-label-success" title="Mark Received"><i class="icon-base ti tabler-truck-delivery"></i></button>' : '') . '
                    <button onclick="deletePo(' . $po->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete"><i class="icon-base ti tabler-trash"></i></button>
                </div>
            ')
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier'         => 'required|string|max:255',
            'expected_date'    => 'nullable|date',
            'notes'            => 'nullable|string|max:1000',
            'items'            => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity'          => 'required|integer|min:1',
            'items.*.unit_price'        => 'required|numeric|min:0',
        ], $this->validationMessages());

        DB::transaction(function () use ($request) {
            $total = 0;
            foreach ($request->items as $item) {
                $total += $item['quantity'] * $item['unit_price'];
            }

            $po = PurchaseOrder::create([
                'supplier'      => $request->supplier,
                'raised_by'     => auth()->id(),
                'total_amount'  => $total,
                'status'        => 'draft',
                'order_date'    => now()->toDateString(),
                'expected_date' => $request->expected_date,
                'notes'         => $request->notes,
            ]);

            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id'  => $po->id,
                    'inventory_item_id'  => $item['inventory_item_id'],
                    'quantity'           => $item['quantity'],
                    'unit_price'         => $item['unit_price'],
                    'total_price'        => $item['quantity'] * $item['unit_price'],
                ]);
            }
        });

        return back()->with('success', 'Purchase order created!');
    }

    public function receive(PurchaseOrder $po)
    {
        if ($po->status === 'received') {
            return response()->json(['error' => 'Purchase order already received.'], 422);
        }

        DB::transaction(function () use ($po) {
            $this->bulkIncrementStock($po->items);

            $po->update([
                'status'         => 'received',
                'received_date'  => now()->toDateString(),
            ]);
        });

        return response()->json(['success' => true]);
    }

    public function destroy(PurchaseOrder $po)
    {
        $po->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Was one UPDATE per PO line item (a real N+1 for large orders) — now a
     * single batched CASE-WHEN update. Groups by inventory_item_id first so a
     * PO with the same item on two lines increments it once by the combined
     * quantity, instead of two separate (still-correct, just less efficient)
     * writes.
     */
    private function bulkIncrementStock($items): void
    {
        $quantitiesById = $items->groupBy('inventory_item_id')
            ->map(fn ($group) => $group->sum('quantity'));

        if ($quantitiesById->isEmpty()) {
            return;
        }

        $caseSql = [];
        $bindings = [];
        foreach ($quantitiesById as $inventoryItemId => $quantity) {
            $caseSql[] = 'WHEN ? THEN current_stock + ?';
            $bindings[] = $inventoryItemId;
            $bindings[] = $quantity;
        }
        $ids = $quantitiesById->keys();
        $bindings = array_merge($bindings, $ids->all());
        $placeholders = implode(',', array_fill(0, $ids->count(), '?'));

        DB::update(
            'UPDATE inventory_items SET current_stock = CASE id ' . implode(' ', $caseSql) . ' END WHERE id IN (' . $placeholders . ')',
            $bindings
        );
    }
}
