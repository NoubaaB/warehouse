<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsumableStock;
use Illuminate\Http\Request;

class ConsumableStockController extends Controller
{
    public function index()
    {
        $stocks = ConsumableStock::with('consumableType')
            ->get()
            ->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'consumable_type_id' => $stock->consumable_type_id,
                    'consumable_name' => $stock->consumableType->name ?? 'Unknown',
                    'unit' => $stock->unit ?? ($stock->consumableType->unit ?? 'pcs'),
                    'total_received' => (float)$stock->total_received,
                    'total_consumed' => (float)$stock->total_consumed,
                    'current_quantity' => (float)$stock->current_quantity,
                    'status' => $stock->current_quantity <= 10 ? 'low_stock' : 'normal',
                ];
            });

        return response()->json($stocks);
    }
}
