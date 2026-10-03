<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workforce;
use Illuminate\Http\Request;

class WorkforceController extends Controller
{
    public function index()
    {
        return response()->json(Workforce::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        $workforce = Workforce::create($validated);
        return response()->json($workforce, 201);
    }

    public function update(Request $request, Workforce $workforce)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        $workforce->update($validated);
        return response()->json($workforce);
    }

    public function destroy(Workforce $workforce)
    {
        $workforce->delete();
        return response()->json(['message' => 'Workforce deleted successfully']);
    }
}
