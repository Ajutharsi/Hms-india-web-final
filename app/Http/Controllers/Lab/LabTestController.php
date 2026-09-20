<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\LabTest;
use Illuminate\Http\Request;

class LabTestController extends Controller
{
    public function index()
    {
        return view('lab.tests.index');
    }

    public function data()
    {
        $tests = LabTest::all();
        return response()->json(['data' => $tests]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'test_name'           => 'required|string|min:2|max:255',
            'category'            => 'required|string|max:255',
            'sample_type'         => 'required|string|max:255',
            'price'               => 'required|numeric|min:0',
            'unit'                => 'nullable|string|max:255',
            'normal_range_male'   => 'nullable|string|max:255',
            'normal_range_female' => 'nullable|string|max:255',
            'description'         => 'nullable|string|max:1000',
            'status'              => 'nullable|in:active,inactive',
        ], $this->validationMessages());

        LabTest::create($validated);

        return redirect()->route('lab.tests.index')
            ->with('success', 'Lab test added!');
    }

    public function getJson(LabTest $labTest)
    {
        return response()->json($labTest);
    }

    public function update(Request $request, LabTest $labTest)
    {
        $validated = $request->validate([
            'test_name'           => 'required|string|min:2|max:255',
            'category'            => 'required|string|max:255',
            'sample_type'         => 'required|string|max:255',
            'price'               => 'required|numeric|min:0',
            'unit'                => 'nullable|string|max:255',
            'normal_range_male'   => 'nullable|string|max:255',
            'normal_range_female' => 'nullable|string|max:255',
            'description'         => 'nullable|string|max:1000',
            'status'              => 'nullable|in:active,inactive',
        ], $this->validationMessages());

        $labTest->update($validated);

        return redirect()->route('lab.tests.index')
            ->with('success', 'Lab test updated!');
    }

    public function destroy(LabTest $labTest)
    {
        $labTest->delete();
        return response()->json(['success' => true]);
    }
}