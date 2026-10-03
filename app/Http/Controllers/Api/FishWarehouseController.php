<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FishWarehouse;
use Illuminate\Http\Request;

class FishWarehouseController extends Controller
{
    public function index()
    {
        return response()->json(FishWarehouse::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $warehouse = FishWarehouse::create($validated);
        return response()->json($warehouse, 201);
    }

    public function update(Request $request, FishWarehouse $fishWarehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $fishWarehouse->update($validated);
        return response()->json($fishWarehouse);
    }

    public function destroy(FishWarehouse $fishWarehouse)
    {
        $fishWarehouse->delete();
        return response()->json(['message' => 'Fish warehouse deleted successfully']);
    }
}
