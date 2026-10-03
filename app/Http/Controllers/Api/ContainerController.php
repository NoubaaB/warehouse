<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Container;
use Illuminate\Http\Request;

class ContainerController extends Controller
{
    public function index()
    {
        return response()->json(Container::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'active' => 'boolean',
        ]);

        $container = Container::create($validated);
        return response()->json($container, 201);
    }

    public function update(Request $request, Container $container)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'active' => 'boolean',
        ]);

        $container->update($validated);
        return response()->json($container);
    }

    public function destroy(Container $container)
    {
        $container->delete();
        return response()->json(['message' => 'Container deleted successfully']);
    }
}
