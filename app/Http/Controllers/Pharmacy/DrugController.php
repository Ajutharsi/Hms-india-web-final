<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Models\DrugStock;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DrugController extends Controller
{
    public function index()
    {
        $lowStockCount  = Drug::whereColumn('current_stock', '<=', 'min_stock_level')->count();
        $expiryCount    = Drug::whereNotNull('expiry_date')
                            ->whereDate('expiry_date', '<=', now()->addDays(30))
                            ->count();
        $inventoryItems = InventoryItem::orderBy('item_name')->get(['id', 'item_name', 'item_code']);
        return view('pharmacy.drugs.index', compact('lowStockCount', 'expiryCount', 'inventoryItems'));
    }

    public function data()
    {
        $query = Drug::query()->latest();

        return DataTables::of($query)
            ->addColumn('name_info', fn($d) => '<div><strong>' . e($d->drug_name) . '</strong>' .
                ($d->generic_name ? '<br><small class="text-muted">' . e($d->generic_name) . '</small>' : '') . '</div>')
            ->addColumn('category_badge', fn($d) => '<span class="badge bg-label-primary">' . $d->category . '</span>')
            ->addColumn('form_badge', fn($d) => '<span class="badge bg-label-info">' . $d->form . '</span>')
            ->addColumn('stock_display', function ($d) {
                if ($d->isExpired()) {
                    return '<span class="text-danger fw-bold">' . $d->current_stock . ' <small>(Expired)</small></span>';
                } elseif ($d->isLowStock()) {
                    return '<span class="text-danger fw-bold">' . $d->current_stock . ' <i class="icon-base ti tabler-alert-triangle ms-1"></i></span>';
                }
                return '<span class="text-success fw-bold">' . $d->current_stock . '</span>';
            })
            ->addColumn('unit_price_fmt', fn($d) => 'LKR ' . number_format($d->unit_price, 2))
            ->addColumn('expiry_display', function ($d) {
                if (!$d->expiry_date) return '<span class="text-muted">N/A</span>';
                $formatted = $d->expiry_date->format('d M Y');
                if ($d->isExpired()) return '<span class="badge bg-label-danger">' . $formatted . '</span>';
                if ($d->isNearExpiry()) return '<span class="badge bg-label-warning">' . $formatted . '</span>';
                return '<span class="text-muted">' . $formatted . '</span>';
            })
            ->addColumn('status_badge', fn($d) => '<span class="badge bg-label-' . ($d->status === 'active' ? 'success' : 'danger') . '">' . $d->status . '</span>')
            ->addColumn('actions', fn($d) => '
                <div class="d-flex gap-1">
                    <button onclick="editDrug(' . $d->id . ')" class="btn btn-sm btn-icon btn-label-warning" title="Edit">
                        <i class="icon-base ti tabler-edit"></i>
                    </button>
                    <button onclick="addStock(' . $d->id . ')" class="btn btn-sm btn-icon btn-label-success" title="Add Stock">
                        <i class="icon-base ti tabler-plus"></i>
                    </button>
                    <button onclick="deleteDrug(' . $d->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete">
                        <i class="icon-base ti tabler-trash"></i>
                    </button>
                </div>
            ')
            ->rawColumns(['name_info', 'category_badge', 'form_badge', 'stock_display', 'expiry_display', 'status_badge', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'drug_name'       => 'required|string|min:2|max:255',
            'generic_name'    => 'nullable|string|max:255',
            'category'        => 'required|string|max:255',
            'form'            => 'required|string|max:255',
            'strength'        => 'nullable|string|max:255',
            'manufacturer'    => 'nullable|string|max:255',
            'unit_price'      => 'required|numeric|min:0',
            'current_stock'   => 'nullable|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'expiry_date'     => 'nullable|date',
            'status'          => 'nullable|in:active,inactive',
            'inventory_item_id' => 'nullable|exists:inventory_items,id',
        ], $this->validationMessages());

        $drug = Drug::create($validated);

        // Initial stock entry
        if (($validated['current_stock'] ?? 0) > 0) {
            DrugStock::create([
                'drug_id'      => $drug->id,
                'type'         => 'in',
                'quantity'     => $validated['current_stock'],
                'balance'      => $validated['current_stock'],
                'reason'       => 'Initial Stock',
                'performed_by' => auth()->id(),
            ]);
        }

        return redirect()->route('pharmacy.drugs.index')
            ->with('success', 'Drug added successfully!');
    }

    public function getJson(Drug $drug)
    {
        return response()->json($drug);
    }

    public function update(Request $request, Drug $drug)
    {
        $validated = $request->validate([
            'drug_name'       => 'required|string|min:2|max:255',
            'generic_name'    => 'nullable|string|max:255',
            'category'        => 'required|string|max:255',
            'form'            => 'required|string|max:255',
            'strength'        => 'nullable|string|max:255',
            'manufacturer'    => 'nullable|string|max:255',
            'unit_price'      => 'required|numeric|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'expiry_date'     => 'nullable|date',
            'status'          => 'nullable|in:active,inactive',
            'inventory_item_id' => 'nullable|exists:inventory_items,id',
        ], $this->validationMessages());

        $drug->update($validated);

        return redirect()->route('pharmacy.drugs.index')
            ->with('success', 'Drug updated!');
    }

    public function destroy(Drug $drug)
    {
        $drug->delete();
        return response()->json(['success' => true]);
    }

    public function addStock(Request $request, Drug $drug)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason'   => 'required|string|max:255',
            'notes'    => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $newBalance = $drug->current_stock + $request->quantity;

        DrugStock::create([
            'drug_id'      => $drug->id,
            'type'         => 'in',
            'quantity'     => $request->quantity,
            'balance'      => $newBalance,
            'reason'       => $request->reason,
            'reference'    => $request->reference ?? null,
            'notes'        => $request->notes ?? null,
            'performed_by' => auth()->id(),
        ]);

        $drug->update(['current_stock' => $newBalance]);

        return redirect()->route('pharmacy.drugs.index')
            ->with('success', 'Stock added!');
    }

    public function stockHistory(Drug $drug)
    {
        $stocks = $drug->stocks()->with('performedBy')->latest()->get();
        return response()->json($stocks);
    }
}