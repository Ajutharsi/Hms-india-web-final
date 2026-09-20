<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingService;
use Illuminate\Http\Request;

class BillingServiceController extends Controller
{
    public function index()
    {
        return view('billing.services.index');
    }

    public function data()
    {
        $services = BillingService::all();
        return response()->json(['data' => $services]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_name' => 'required|string|min:2|max:255',
            'category'     => 'required|string|max:255',
            'price'        => 'required|numeric|min:0',
            'description'  => 'nullable|string|max:1000',
            'status'       => 'nullable|in:active,inactive',
        ], $this->validationMessages());

        BillingService::create($validated);

        return redirect()->route('billing.services.index')
            ->with('success', 'Service added!');
    }

    public function getJson(BillingService $billingService)
    {
        return response()->json($billingService);
    }

    public function update(Request $request, BillingService $billingService)
    {
        $validated = $request->validate([
            'service_name' => 'required|string|min:2|max:255',
            'category'     => 'required|string|max:255',
            'price'        => 'required|numeric|min:0',
            'description'  => 'nullable|string|max:1000',
            'status'       => 'nullable|in:active,inactive',
        ], $this->validationMessages());

        $billingService->update($validated);

        return redirect()->route('billing.services.index')
            ->with('success', 'Service updated!');
    }

    public function destroy(BillingService $billingService)
    {
        $billingService->delete();
        return response()->json(['success' => true]);
    }
}