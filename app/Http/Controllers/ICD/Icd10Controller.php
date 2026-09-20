<?php

namespace App\Http\Controllers\ICD;

use App\Http\Controllers\Controller;
use App\Models\Icd10Code;
use Illuminate\Http\Request;

class Icd10Controller extends Controller
{
    public function index()
    {
        return view('icd10.index');
    }

    public function data()
    {
        $codes = Icd10Code::orderBy('code')->get();
        return response()->json(['data' => $codes]);
    }

    public function search(Request $request)
    {
        $term = $request->get('q', '');

        $codes = Icd10Code::where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            })
            ->orderBy('code')
            ->limit(20)
            ->get(['id', 'code', 'description']);

        return response()->json([
            'results' => $codes->map(fn($c) => [
                'id'   => $c->code,
                'text' => "{$c->code} — {$c->description}",
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'        => 'required|string|max:20|unique:icd10_codes,code',
            'description' => 'required|string|max:500',
            'category'    => 'nullable|string|max:255',
            'chapter'     => 'nullable|string|max:255',
        ], $this->validationMessages());

        Icd10Code::create($request->only(['code', 'description', 'category', 'chapter']) + ['is_active' => true]);

        return back()->with('success', 'ICD-10 code added!');
    }

    public function update(Request $request, Icd10Code $code)
    {
        $request->validate([
            'code'        => 'required|string|max:20|unique:icd10_codes,code,' . $code->id,
            'description' => 'required|string|max:500',
            'category'    => 'nullable|string|max:255',
            'chapter'     => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
        ], $this->validationMessages());

        $code->update([
            'code'        => $request->code,
            'description' => $request->description,
            'category'    => $request->category,
            'chapter'     => $request->chapter,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'ICD-10 code updated!');
    }

    public function destroy(Icd10Code $code)
    {
        $code->delete();
        return response()->json(['success' => true]);
    }
}
