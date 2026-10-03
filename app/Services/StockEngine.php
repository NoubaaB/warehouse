<?php

namespace App\Services;

use App\Models\ArchivedFishStock;
use App\Models\Container;
use App\Models\ConsumableStock;
use App\Models\ConsumableType;
use App\Models\FishStock;
use App\Models\Voucher;
use App\Models\VoucherArticleDetail;
use App\Models\ConsumableReceiptDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockEngine
{
    /**
     * Process stock effects for a confirmed voucher.
     */
    public function processVoucher(Voucher $voucher): void
    {
        DB::transaction(function () use ($voucher) {
            $effect = $voucher->type->effect;

            if ($effect === 'stock_in') {
                $this->processStockIn($voucher);
            } elseif ($effect === 'stock_out') {
                $this->processStockOut($voucher);
            } elseif ($effect === 'consumable_stock_in') {
                $this->processConsumableStockIn($voucher);
            }
        });
    }

    /**
     * Process Reception Voucher (Fish Stock In + Consumable Consumption).
     */
    protected function processStockIn(Voucher $voucher): void
    {
        $providerNames = $voucher->providers->pluck('name')->implode(', ');

        foreach ($voucher->articleDetails as $detail) {
            // 1. Calculate boxes
            $calculatedBoxes = 0;
            if ($detail->container_id) {
                $container = Container::find($detail->container_id);
                if ($container && $container->capacity > 0) {
                    $calculatedBoxes = round($detail->quantity / $container->capacity, 2);
                }
            }
            $detail->update(['calculated_boxes' => $calculatedBoxes]);

            // 2. Create Active Fish Stock Lot
            $stock = FishStock::create([
                'voucher_id' => $voucher->id,
                'freezing_fish_id' => $detail->freezing_fish_id,
                'fish_warehouse_id' => $detail->fish_warehouse_id ?? $voucher->fish_warehouse_id,
                'container_id' => $detail->container_id,
                'reception_date' => $voucher->voucher_date,
                'provider_names' => $providerNames,
                'original_quantity' => $detail->quantity,
                'remaining_quantity' => $detail->quantity,
                'calculated_boxes' => $calculatedBoxes,
                'status' => 'active',
            ]);

            $detail->update(['fish_stock_id' => $stock->id]);

            // 3. Process Consumables consumed by this article detail
            foreach ($detail->consumableDetails as $cDetail) {
                $cStock = ConsumableStock::firstOrCreate(
                    ['consumable_type_id' => $cDetail->consumable_type_id],
                    [
                        'total_received' => 0,
                        'total_consumed' => 0,
                        'current_quantity' => 0,
                        'unit' => $cDetail->unit ?? 'pcs',
                    ]
                );

                // Lock consumable stock row for update
                $cStock = ConsumableStock::where('id', $cStock->id)->lockForUpdate()->first();

                if ($cStock->current_quantity < $cDetail->quantity) {
                    $cType = ConsumableType::find($cDetail->consumable_type_id);
                    $typeName = $cType ? $cType->name : 'Consumable';
                    throw ValidationException::withMessages([
                        'consumable_stock' => "Insufficient stock for consumable '{$typeName}'. Available: {$cStock->current_quantity} {$cStock->unit}, Required: {$cDetail->quantity} {$cStock->unit}.",
                    ]);
                }

                $cStock->total_consumed += $cDetail->quantity;
                $cStock->current_quantity -= $cDetail->quantity;
                $cStock->save();
            }
        }
    }

    /**
     * Process Stock-Out / Sales Voucher (Deduct Fish Stock & Archive if remaining = 0).
     */
    protected function processStockOut(Voucher $voucher): void
    {
        foreach ($voucher->articleDetails as $detail) {
            if (!$detail->fish_stock_id) {
                throw ValidationException::withMessages([
                    'stock' => "Sales detail for fish must select an active fish stock lot.",
                ]);
            }

            // Lock stock row for update
            $stock = FishStock::where('id', $detail->fish_stock_id)->lockForUpdate()->first();

            if (!$stock || $stock->status !== 'active') {
                throw ValidationException::withMessages([
                    'stock' => "Selected fish stock lot is no longer active.",
                ]);
            }

            // Rule: stock.reception_date <= voucher.voucher_date
            if ($stock->reception_date->gt($voucher->voucher_date)) {
                throw ValidationException::withMessages([
                    'voucher_date' => "Cannot sell from stock lot received on {$stock->reception_date->format('Y-m-d')} using stock-out date {$voucher->voucher_date->format('Y-m-d')}.",
                ]);
            }

            if ($detail->quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Sale quantity must be greater than zero.",
                ]);
            }

            if ($stock->remaining_quantity < $detail->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Cannot sell {$detail->quantity} kg. Only {$stock->remaining_quantity} kg remaining in selected stock lot.",
                ]);
            }

            // Deduct sold quantity
            $stock->remaining_quantity -= $detail->quantity;

            // Recalculate remaining boxes
            if ($stock->container_id) {
                $container = Container::find($stock->container_id);
                if ($container && $container->capacity > 0) {
                    $stock->calculated_boxes = round($stock->remaining_quantity / $container->capacity, 2);
                }
            }

            if ($stock->remaining_quantity <= 0.001) {
                $stock->remaining_quantity = 0;
                $stock->calculated_boxes = 0;
                $stock->status = 'archived';
                $stock->save();

                // Create archive record
                ArchivedFishStock::create([
                    'original_stock_id' => $stock->id,
                    'voucher_id' => $voucher->id,
                    'voucher_article_detail_id' => $detail->id,
                    'freezing_fish_id' => $stock->freezing_fish_id,
                    'fish_warehouse_id' => $stock->fish_warehouse_id,
                    'container_id' => $stock->container_id,
                    'original_quantity' => $stock->original_quantity,
                    'final_quantity' => 0,
                    'reception_date' => $stock->reception_date,
                    'provider_names' => $stock->provider_names,
                    'archived_at' => now(),
                ]);
            } else {
                $stock->save();
            }
        }
    }

    /**
     * Process Consumable Receipt Voucher (Increase Consumable Stock).
     */
    protected function processConsumableStockIn(Voucher $voucher): void
    {
        foreach ($voucher->consumableReceiptDetails as $detail) {
            $cStock = ConsumableStock::firstOrCreate(
                ['consumable_type_id' => $detail->consumable_type_id],
                [
                    'total_received' => 0,
                    'total_consumed' => 0,
                    'current_quantity' => 0,
                    'unit' => $detail->unit ?? 'pcs',
                ]
            );

            $cStock = ConsumableStock::where('id', $cStock->id)->lockForUpdate()->first();

            $cStock->total_received += $detail->quantity;
            $cStock->current_quantity += $detail->quantity;
            $cStock->unit = $detail->unit ?? $cStock->unit;
            $cStock->save();
        }
    }
}
