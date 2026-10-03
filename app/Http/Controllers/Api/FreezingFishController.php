<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FreezingFish;
use Illuminate\Http\Request;

class FreezingFishController extends Controller
{
    public function index()
    {
        return response()->json(FreezingFish::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        $fish = FreezingFish::create($validated);
        return response()->json($fish, 201);
    }

    public function update(Request $request, FreezingFish $freezingFish)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        $freezingFish->update($validated);
        return response()->json($freezingFish);
    }

    public function destroy(FreezingFish $freezingFish)
    {
        $freezingFish->delete();
        return response()->json(['message' => 'Freezing fish deleted successfully']);
    }
}
