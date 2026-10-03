<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsumableStock;
use App\Models\ConsumableType;
use Illuminate\Http\Request;

class ConsumableTypeController extends Controller
{
    public function index()
    {
        return response()->json(ConsumableType::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'active' => 'boolean',
        ]);

        $type = ConsumableType::create($validated);
        
        ConsumableStock::firstOrCreate(
            ['consumable_type_id' => $type->id],
            [
                'total_received' => 0,
                'total_consumed' => 0,
                'current_quantity' => 0,
                'unit' => $type->unit,
            ]
        );

        return response()->json($type, 201);
    }

    public function update(Request $request, ConsumableType $consumableType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'active' => 'boolean',
        ]);

        $consumableType->update($validated);
        return response()->json($consumableType);
    }

    public function destroy(ConsumableType $consumableType)
    {
        $consumableType->delete();
        return response()->json(['message' => 'Consumable type deleted successfully']);
    }
}
