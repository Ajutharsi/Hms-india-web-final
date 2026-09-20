<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return view('settings.departments');
    }

    public function data()
    {
        $departments = Department::all();
        return response()->json(['data' => $departments]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        $dept = Department::create($request->all());

        ActivityLog::log('created', 'Department', 'Department ' . $dept->name . ' created', $dept);

        return redirect()->route('settings.departments')
            ->with('success', 'Department added!');
    }

    public function getJson(Department $department)
    {
        return response()->json($department);
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        $department->update($request->all());

        ActivityLog::log('updated', 'Department', 'Department ' . $department->name . ' updated', $department);

        return redirect()->route('settings.departments')
            ->with('success', 'Department updated!');
    }

    public function destroy(Department $department)
    {
        ActivityLog::log('deleted', 'Department', 'Department ' . $department->name . ' deleted');
        $department->delete();
        return response()->json(['success' => true]);
    }
}