<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArchivedFishStock;
use App\Models\ConsumableStock;
use App\Models\FishStock;
use App\Models\FishWarehouse;
use App\Models\FreezingFish;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Models\WorkforceAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Total Fish Stock
        $totalFishKg = (float)FishStock::where('status', 'active')->sum('remaining_quantity');
        $activeLotsCount = FishStock::where('status', 'active')->where('remaining_quantity', '>', 0)->count();
        $archivedCount = ArchivedFishStock::count();

        // Stock by Fish Type
        $stockByFish = FreezingFish::get()->map(function ($fish) {
            $kg = (float)FishStock::where('status', 'active')
                ->where('freezing_fish_id', $fish->id)
                ->sum('remaining_quantity');
            return [
                'id' => $fish->id,
                'name' => $fish->name,
                'quantity_kg' => round($kg, 2),
            ];
        });

        // Stock by Warehouse + Donut chart calculation
        $warehouses = FishWarehouse::get();
        $stockByWarehouse = $warehouses->map(function ($wh) use ($totalFishKg) {
            $kg = (float)FishStock::where('status', 'active')
                ->where('fish_warehouse_id', $wh->id)
                ->sum('remaining_quantity');
            $percentage = $totalFishKg > 0 ? round(($kg / $totalFishKg) * 100, 2) : 0;
            return [
                'id' => $wh->id,
                'name' => $wh->name,
                'quantity_kg' => round($kg, 2),
                'percentage' => $percentage,
            ];
        });

        // 2. Consumables
        $consumablesSummary = ConsumableStock::with('consumableType')->get()->map(function ($cs) {
            return [
                'id' => $cs->id,
                'name' => $cs->consumableType->name ?? 'Unknown',
                'current_quantity' => (float)$cs->current_quantity,
                'unit' => $cs->unit,
            ];
        });

        // 3. Vouchers summary
        $receptionTypeId = VoucherType::where('code', 'reception')->value('id');
        $stockOutTypeId = VoucherType::where('code', 'stock_out')->value('id');
        $consumableReceiptTypeId = VoucherType::where('code', 'consumable_receipt')->value('id');

        $totalReceptionVouchers = $receptionTypeId ? Voucher::where('voucher_type_id', $receptionTypeId)->count() : 0;
        $totalStockOutVouchers = $stockOutTypeId ? Voucher::where('voucher_type_id', $stockOutTypeId)->count() : 0;
        $totalConsumableVouchers = $consumableReceiptTypeId ? Voucher::where('voucher_type_id', $consumableReceiptTypeId)->count() : 0;

        // Recent Vouchers
        $recentVouchers = Voucher::with(['type', 'fishWarehouse', 'providers', 'clients'])
            ->latest()
            ->take(5)
            ->get();

        // Current Month Workforce Cost
        $currentMonthCost = (float)WorkforceAssignment::whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('daily_rate');

        return response()->json([
            'total_fish_kg' => round($totalFishKg, 2),
            'active_lots_count' => $activeLotsCount,
            'archived_count' => $archivedCount,
            'stock_by_fish' => $stockByFish,
            'stock_by_warehouse' => $stockByWarehouse,
            'consumables_summary' => $consumablesSummary,
            'vouchers_stats' => [
                'reception_count' => $totalReceptionVouchers,
                'stock_out_count' => $totalStockOutVouchers,
                'consumable_receipt_count' => $totalConsumableVouchers,
                'total_vouchers' => $totalReceptionVouchers + $totalStockOutVouchers + $totalConsumableVouchers,
            ],
            'recent_vouchers' => $recentVouchers,
            'current_month_workforce_cost' => round($currentMonthCost, 2),
            'chart_data' => [
                'labels' => $stockByWarehouse->pluck('name'),
                'series' => $stockByWarehouse->pluck('quantity_kg'),
            ],
        ]);
    }
}
