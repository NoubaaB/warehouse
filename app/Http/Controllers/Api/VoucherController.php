<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Container;
use App\Models\Voucher;
use App\Models\VoucherArticleDetail;
use App\Models\ArticleConsumableDetail;
use App\Models\ConsumableReceiptDetail;
use App\Models\VoucherType;
use App\Services\StockEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoucherController extends Controller
{
    protected StockEngine $stockEngine;

    public function __construct(StockEngine $stockEngine)
    {
        $this->stockEngine = $stockEngine;
    }

    public function index(Request $request)
    {
        $query = Voucher::with([
            'type',
            'fishWarehouse',
            'providers',
            'clients',
            'articleDetails.freezingFish',
            'articleDetails.container',
            'articleDetails.fishWarehouse',
            'articleDetails.consumableDetails.consumableType',
            'consumableReceiptDetails.consumableType',
        ])->latest('voucher_date')->latest('id');

        if ($request->has('voucher_type_id') && $request->voucher_type_id) {
            $query->where('voucher_type_id', $request->voucher_type_id);
        }

        if ($request->has('fish_warehouse_id') && $request->fish_warehouse_id) {
            $query->where('fish_warehouse_id', $request->fish_warehouse_id);
        }

        if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
            $query->whereBetween('voucher_date', [$request->start_date, $request->end_date]);
        }

        return response()->json($query->get());
    }

    public function show(Voucher $voucher)
    {
        $voucher->load([
            'type',
            'fishWarehouse',
            'providers',
            'clients',
            'articleDetails.freezingFish',
            'articleDetails.container',
            'articleDetails.fishWarehouse',
            'articleDetails.consumableDetails.consumableType',
            'consumableReceiptDetails.consumableType',
        ]);

        return response()->json($voucher);
    }

    public function store(Request $request)
    {
        $request->validate([
            'voucher_type_id' => 'required|exists:voucher_types,id',
            'voucher_number' => 'required|string|max:255|unique:vouchers,voucher_number',
            'voucher_date' => 'required|date',
            'truck_licence' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'fish_warehouse_id' => 'nullable|exists:fish_warehouses,id',
            'provider_ids' => 'nullable|array',
            'provider_ids.*' => 'exists:providers,id',
            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
        ]);

        $voucherType = VoucherType::findOrFail($request->voucher_type_id);

        $voucher = DB::transaction(function () use ($request, $voucherType) {
            $voucher = Voucher::create([
                'voucher_number' => $request->voucher_number,
                'voucher_type_id' => $voucherType->id,
                'fish_warehouse_id' => $request->fish_warehouse_id,
                'voucher_date' => $request->voucher_date,
                'truck_licence' => $request->truck_licence,
                'notes' => $request->notes,
                'status' => 'confirmed',
                'created_by' => $request->user()?->id,
            ]);

            // Sync providers & clients
            if (!empty($request->provider_ids)) {
                $voucher->providers()->sync($request->provider_ids);
            }
            if (!empty($request->client_ids)) {
                $voucher->clients()->sync($request->client_ids);
            }

            // Save details based on voucher type effect
            if ($voucherType->effect === 'stock_in') {
                $this->storeStockInDetails($request, $voucher);
            } elseif ($voucherType->effect === 'stock_out') {
                $this->storeStockOutDetails($request, $voucher);
            } elseif ($voucherType->effect === 'consumable_stock_in') {
                $this->storeConsumableReceiptDetails($request, $voucher);
            }

            // Process Stock Logic inside same transaction!
            $this->stockEngine->processVoucher($voucher);

            return $voucher;
        });

        $voucher->load([
            'type',
            'fishWarehouse',
            'providers',
            'clients',
            'articleDetails.freezingFish',
            'articleDetails.container',
            'articleDetails.consumableDetails.consumableType',
            'consumableReceiptDetails.consumableType',
        ]);

        return response()->json([
            'message' => 'Voucher created and stock updated successfully',
            'voucher' => $voucher,
        ], 201);
    }

    protected function storeStockInDetails(Request $request, Voucher $voucher): void
    {
        $articles = $request->input('articles', []);

        if (empty($articles)) {
            throw ValidationException::withMessages([
                'articles' => 'At least one fish article detail is required for reception.',
            ]);
        }

        foreach ($articles as $art) {
            $calcBoxes = 0;
            if (!empty($art['container_id'])) {
                $cnt = Container::find($art['container_id']);
                if ($cnt && $cnt->capacity > 0) {
                    $calcBoxes = round($art['quantity'] / $cnt->capacity, 2);
                }
            }

            $detail = VoucherArticleDetail::create([
                'voucher_id' => $voucher->id,
                'freezing_fish_id' => $art['freezing_fish_id'],
                'container_id' => $art['container_id'] ?? null,
                'fish_warehouse_id' => $art['fish_warehouse_id'] ?? $voucher->fish_warehouse_id,
                'quantity' => $art['quantity'],
                'calculated_boxes' => $calcBoxes,
                'notes' => $art['notes'] ?? null,
            ]);

            if (!empty($art['consumables'])) {
                foreach ($art['consumables'] as $c) {
                    ArticleConsumableDetail::create([
                        'voucher_article_detail_id' => $detail->id,
                        'consumable_type_id' => $c['consumable_type_id'],
                        'quantity' => $c['quantity'],
                        'unit' => $c['unit'] ?? 'pcs',
                        'note' => $c['note'] ?? null,
                    ]);
                }
            }
        }
    }

    protected function storeStockOutDetails(Request $request, Voucher $voucher): void
    {
        $articles = $request->input('articles', []);

        if (empty($articles)) {
            throw ValidationException::withMessages([
                'articles' => 'At least one sales line is required.',
            ]);
        }

        foreach ($articles as $art) {
            $qty = (float)$art['quantity'];
            $price = (float)$art['unit_price'];
            $total = round($qty * $price, 2);

            VoucherArticleDetail::create([
                'voucher_id' => $voucher->id,
                'freezing_fish_id' => $art['freezing_fish_id'],
                'fish_stock_id' => $art['fish_stock_id'],
                'container_id' => $art['container_id'] ?? null,
                'fish_warehouse_id' => $art['fish_warehouse_id'] ?? $voucher->fish_warehouse_id,
                'quantity' => $qty,
                'calculated_boxes' => $art['calculated_boxes'] ?? 0,
                'unit_price' => $price,
                'total_price' => $total,
                'notes' => $art['notes'] ?? null,
            ]);
        }
    }

    protected function storeConsumableReceiptDetails(Request $request, Voucher $voucher): void
    {
        $consumables = $request->input('consumables', []);

        if (empty($consumables)) {
            throw ValidationException::withMessages([
                'consumables' => 'At least one consumable detail is required.',
            ]);
        }

        foreach ($consumables as $c) {
            ConsumableReceiptDetail::create([
                'voucher_id' => $voucher->id,
                'consumable_type_id' => $c['consumable_type_id'],
                'quantity' => $c['quantity'],
                'unit' => $c['unit'] ?? 'pcs',
                'note' => $c['note'] ?? null,
            ]);
        }
    }

    public function destroy(Voucher $voucher)
    {
        // Cancel or prevent deletion if it affects historical stock
        return response()->json([
            'message' => 'Vouchers cannot be physically deleted as they affect stock integrity. Use cancellation if required.',
        ], 422);
    }
}
