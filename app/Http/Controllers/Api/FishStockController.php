<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArchivedFishStock;
use App\Models\FishStock;
use Illuminate\Http\Request;

class FishStockController extends Controller
{
    public function index(Request $request)
    {
        $query = FishStock::with([
            'voucher',
            'freezingFish',
            'fishWarehouse',
            'container',
        ])->where('status', 'active')
          ->where('remaining_quantity', '>', 0);

        if ($request->has('fish_warehouse_id') && $request->fish_warehouse_id) {
            $query->where('fish_warehouse_id', $request->fish_warehouse_id);
        }

        if ($request->has('freezing_fish_id') && $request->freezing_fish_id) {
            $query->where('freezing_fish_id', $request->freezing_fish_id);
        }

        // Filter: reception_date <= selected stock-out date rule!
        if ($request->has('date_lte') && $request->date_lte) {
            $query->where('reception_date', '<=', $request->date_lte);
        }

        if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
            $query->whereBetween('reception_date', [$request->start_date, $request->end_date]);
        }

        return response()->json($query->orderBy('reception_date', 'asc')->get());
    }

    public function archive(Request $request)
    {
        $query = ArchivedFishStock::with([
            'voucher',
            'freezingFish',
            'fishWarehouse',
            'container',
        ]);

        if ($request->has('fish_warehouse_id') && $request->fish_warehouse_id) {
            $query->where('fish_warehouse_id', $request->fish_warehouse_id);
        }

        if ($request->has('freezing_fish_id') && $request->freezing_fish_id) {
            $query->where('freezing_fish_id', $request->freezing_fish_id);
        }

        if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
            $query->whereBetween('reception_date', [$request->start_date, $request->end_date]);
        }

        return response()->json($query->latest('archived_at')->get());
    }
}
