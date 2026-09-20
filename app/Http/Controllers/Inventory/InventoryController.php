<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class InventoryController extends Controller
{
    public function index()
    {
        return view('inventory.index');
    }

    public function data()
    {
        $query = InventoryItem::query()->latest();

        return DataTables::of($query)
            ->addColumn('stock_display', function ($i) {
                return $i->isLowStock()
                    ? '<span class="badge bg-label-danger">' . $i->current_stock . ' ' . $i->unit . '</span>'
                    : $i->current_stock . ' ' . $i->unit;
            })
            ->addColumn('category_badge', fn($i) => '<span class="badge bg-label-primary">' . str_replace('_', ' ', $i->category) . '</span>')
            ->addColumn('unit_price_fmt', fn($i) => config('hospital.currency') . ' ' . number_format($i->unit_price, 2))
            ->addColumn('status_badge', fn($i) => '<span class="badge bg-label-' . ($i->status === 'active' ? 'success' : 'secondary') . '">' . $i->status . '</span>')
            ->addColumn('actions', fn($i) => '
                <div class="d-flex gap-2">
                    <button onclick="editItem(' . $i->id . ')" class="btn btn-sm btn-icon btn-label-warning"><i class="icon-base ti tabler-edit"></i></button>
                    <button onclick="deleteItem(' . $i->id . ')" class="btn btn-sm btn-icon btn-label-danger"><i class="icon-base ti tabler-trash"></i></button>
                </div>
            ')
            ->rawColumns(['stock_display', 'category_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_name'       => 'required|string|max:255',
            'category'        => 'required|string|max:100',
            'unit'            => 'required|string|max:50',
            'current_stock'   => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
            'unit_price'      => 'required|numeric|min:0',
            'supplier'        => 'nullable|string|max:255',
            'location'        => 'nullable|string|max:255',
        ], $this->validationMessages());

        InventoryItem::create($request->only([
            'item_name', 'category', 'unit', 'current_stock',
            'min_stock_level', 'unit_price', 'supplier', 'location',
        ]));

        return back()->with('success', 'Inventory item added!');
    }

    public function getJson(InventoryItem $item)
    {
        return response()->json($item);
    }

    public function update(Request $request, InventoryItem $item)
    {
        $request->validate([
            'item_name'       => 'required|string|max:255',
            'category'        => 'required|string|max:100',
            'unit'            => 'required|string|max:50',
            'current_stock'   => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
            'unit_price'      => 'required|numeric|min:0',
            'supplier'        => 'nullable|string|max:255',
            'location'        => 'nullable|string|max:255',
            'status'          => 'required|in:active,inactive',
        ], $this->validationMessages());

        $item->update($request->only([
            'item_name', 'category', 'unit', 'current_stock',
            'min_stock_level', 'unit_price', 'supplier', 'location', 'status',
        ]));

        return back()->with('success', 'Inventory item updated!');
    }

    public function destroy(InventoryItem $item)
    {
        $item->delete();
        return response()->json(['success' => true]);
    }
}
