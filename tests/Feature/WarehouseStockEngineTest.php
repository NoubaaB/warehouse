<?php

namespace Tests\Feature;

use App\Models\ConsumableStock;
use App\Models\ConsumableType;
use App\Models\Container;
use App\Models\FishStock;
use App\Models\FishWarehouse;
use App\Models\FreezingFish;
use App\Models\Provider;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Models\Workforce;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WarehouseStockEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function test_1_and_2_reception_and_consumable_usage()
    {
        $user = User::first();
        $warehouse = FishWarehouse::where('name', 'Freezer 1')->first();
        $fish = FreezingFish::where('name', 'Sardine HGT')->first();
        $container = Container::where('name', 'Caisse 24')->first();
        $provider = Provider::where('name', 'Provider A')->first();
        $recType = VoucherType::where('code', 'reception')->first();

        // Set initial consumable stocks
        $cartonType = ConsumableType::where('name', 'Carton')->first();
        $celloType = ConsumableType::where('name', 'Cellophane')->first();
        $strapexType = ConsumableType::where('name', 'Strapex')->first();

        ConsumableStock::where('consumable_type_id', $cartonType->id)->update(['current_quantity' => 100, 'total_received' => 100, 'total_consumed' => 0]);
        ConsumableStock::where('consumable_type_id', $celloType->id)->update(['current_quantity' => 20, 'total_received' => 20, 'total_consumed' => 0]);
        ConsumableStock::where('consumable_type_id', $strapexType->id)->update(['current_quantity' => 50, 'total_received' => 50, 'total_consumed' => 0]);

        $response = $this->actingAs($user)->postJson('/api/vouchers', [
            'voucher_type_id' => $recType->id,
            'voucher_number' => 'REC-TEST-001',
            'voucher_date' => '2026-10-01',
            'fish_warehouse_id' => $warehouse->id,
            'provider_ids' => [$provider->id],
            'truck_licence' => '12345-A-1',
            'articles' => [
                [
                    'freezing_fish_id' => $fish->id,
                    'quantity' => 1500,
                    'container_id' => $container->id,
                    'fish_warehouse_id' => $warehouse->id,
                    'consumables' => [
                        ['consumable_type_id' => $cartonType->id, 'quantity' => 62.5, 'unit' => 'pcs'],
                        ['consumable_type_id' => $celloType->id, 'quantity' => 1, 'unit' => 'rolls'],
                        ['consumable_type_id' => $strapexType->id, 'quantity' => 2, 'unit' => 'rolls'],
                    ],
                ]
            ],
        ]);

        $response->assertStatus(201);

        // Test 1 Assertions: Calculated boxes = 62.5, Fish stock increases by 1500 kg
        $stock = FishStock::where('voucher_id', $response->json('voucher.id'))->first();
        $this->assertNotNull($stock);
        $this->assertEquals(1500.00, $stock->remaining_quantity);
        $this->assertEquals(62.50, $stock->calculated_boxes);

        // Test 2 Assertions: Consumable stock after reception: Carton = 37.5, Cellophane = 19, Strapex = 48
        $this->assertEquals(37.50, ConsumableStock::where('consumable_type_id', $cartonType->id)->value('current_quantity'));
        $this->assertEquals(19.00, ConsumableStock::where('consumable_type_id', $celloType->id)->value('current_quantity'));
        $this->assertEquals(48.00, ConsumableStock::where('consumable_type_id', $strapexType->id)->value('current_quantity'));
    }

    #[Test]
    public function test_3_and_4_sale_and_date_filtering()
    {
        $user = User::first();
        $warehouse = FishWarehouse::first();
        $fish = FreezingFish::first();
        $recType = VoucherType::where('code', 'reception')->first();
        $soType = VoucherType::where('code', 'stock_out')->first();

        // Reception A on 2026-10-01
        $vA = Voucher::create([
            'voucher_number' => 'REC-A',
            'voucher_type_id' => $recType->id,
            'fish_warehouse_id' => $warehouse->id,
            'voucher_date' => '2026-10-01',
            'status' => 'confirmed',
        ]);
        $stockA = FishStock::create([
            'voucher_id' => $vA->id,
            'freezing_fish_id' => $fish->id,
            'fish_warehouse_id' => $warehouse->id,
            'reception_date' => '2026-10-01',
            'original_quantity' => 1500,
            'remaining_quantity' => 1500,
            'status' => 'active',
        ]);

        // Reception B on 2026-10-10
        $vB = Voucher::create([
            'voucher_number' => 'REC-B',
            'voucher_type_id' => $recType->id,
            'fish_warehouse_id' => $warehouse->id,
            'voucher_date' => '2026-10-10',
            'status' => 'confirmed',
        ]);
        $stockB = FishStock::create([
            'voucher_id' => $vB->id,
            'freezing_fish_id' => $fish->id,
            'fish_warehouse_id' => $warehouse->id,
            'reception_date' => '2026-10-10',
            'original_quantity' => 2000,
            'remaining_quantity' => 2000,
            'status' => 'active',
        ]);

        // Test 4: Filter available stock lots for stock-out date 2026-10-05
        $filteredResponse = $this->actingAs($user)->getJson('/api/fish-stock?fish_warehouse_id=' . $warehouse->id . '&date_lte=2026-10-05');
        $filteredResponse->assertStatus(200);
        $stockIds = collect($filteredResponse->json())->pluck('id')->toArray();

        $this->assertContains($stockA->id, $stockIds);
        $this->assertNotContains($stockB->id, $stockIds); // Reception B excluded!

        // Test 3: Sell 500 kg from Stock A
        $saleResponse = $this->actingAs($user)->postJson('/api/vouchers', [
            'voucher_type_id' => $soType->id,
            'voucher_number' => 'SO-TEST-001',
            'voucher_date' => '2026-10-05',
            'fish_warehouse_id' => $warehouse->id,
            'articles' => [
                [
                    'fish_stock_id' => $stockA->id,
                    'freezing_fish_id' => $fish->id,
                    'quantity' => 500,
                    'unit_price' => 10,
                ]
            ]
        ]);

        $saleResponse->assertStatus(201);
        $this->assertEquals(1000.00, $stockA->fresh()->remaining_quantity);
    }

    #[Test]
    public function test_5_sold_out_archived()
    {
        $user = User::first();
        $warehouse = FishWarehouse::first();
        $fish = FreezingFish::first();
        $recType = VoucherType::where('code', 'reception')->first();
        $soType = VoucherType::where('code', 'stock_out')->first();

        $vA = Voucher::create([
            'voucher_number' => 'REC-SOLD-OUT',
            'voucher_type_id' => $recType->id,
            'fish_warehouse_id' => $warehouse->id,
            'voucher_date' => '2026-10-01',
            'status' => 'confirmed',
        ]);
        $stock = FishStock::create([
            'voucher_id' => $vA->id,
            'freezing_fish_id' => $fish->id,
            'fish_warehouse_id' => $warehouse->id,
            'reception_date' => '2026-10-01',
            'original_quantity' => 1000,
            'remaining_quantity' => 1000,
            'status' => 'active',
        ]);

        // Sell full 1000 kg
        $saleResponse = $this->actingAs($user)->postJson('/api/vouchers', [
            'voucher_type_id' => $soType->id,
            'voucher_number' => 'SO-FULL-001',
            'voucher_date' => '2026-10-05',
            'fish_warehouse_id' => $warehouse->id,
            'articles' => [
                [
                    'fish_stock_id' => $stock->id,
                    'freezing_fish_id' => $fish->id,
                    'quantity' => 1000,
                    'unit_price' => 12,
                ]
            ]
        ]);

        $saleResponse->assertStatus(201);
        $this->assertEquals('archived', $stock->fresh()->status);
        $this->assertEquals(0, $stock->fresh()->remaining_quantity);

        // Verify archived copy created
        $this->assertDatabaseHas('archived_fish_stocks', [
            'original_stock_id' => $stock->id,
            'original_quantity' => 1000.00,
            'final_quantity' => 0.00,
        ]);
    }

    #[Test]
    public function test_6_prevent_overselling()
    {
        $user = User::first();
        $warehouse = FishWarehouse::first();
        $fish = FreezingFish::first();
        $recType = VoucherType::where('code', 'reception')->first();
        $soType = VoucherType::where('code', 'stock_out')->first();

        $vA = Voucher::create([
            'voucher_number' => 'REC-OVERSELL',
            'voucher_type_id' => $recType->id,
            'fish_warehouse_id' => $warehouse->id,
            'voucher_date' => '2026-10-01',
            'status' => 'confirmed',
        ]);
        $stock = FishStock::create([
            'voucher_id' => $vA->id,
            'freezing_fish_id' => $fish->id,
            'fish_warehouse_id' => $warehouse->id,
            'reception_date' => '2026-10-01',
            'original_quantity' => 500,
            'remaining_quantity' => 500,
            'status' => 'active',
        ]);

        // Attempt to sell 600 kg when only 500 available
        $response = $this->actingAs($user)->postJson('/api/vouchers', [
            'voucher_type_id' => $soType->id,
            'voucher_number' => 'SO-OVERSELL-001',
            'voucher_date' => '2026-10-05',
            'fish_warehouse_id' => $warehouse->id,
            'articles' => [
                [
                    'fish_stock_id' => $stock->id,
                    'freezing_fish_id' => $fish->id,
                    'quantity' => 600,
                    'unit_price' => 15,
                ]
            ]
        ]);

        $response->assertStatus(422);
        $this->assertEquals(500.00, $stock->fresh()->remaining_quantity); // Database unchanged!
    }

    #[Test]
    public function test_7_consumable_negative_stock_prevention()
    {
        $user = User::first();
        $warehouse = FishWarehouse::first();
        $fish = FreezingFish::first();
        $recType = VoucherType::where('code', 'reception')->first();
        $cartonType = ConsumableType::where('name', 'Carton')->first();

        ConsumableStock::where('consumable_type_id', $cartonType->id)->update(['current_quantity' => 20]);

        // Attempt to consume 25 cartons when only 20 in stock
        $response = $this->actingAs($user)->postJson('/api/vouchers', [
            'voucher_type_id' => $recType->id,
            'voucher_number' => 'REC-NEG-CONS',
            'voucher_date' => '2026-10-01',
            'fish_warehouse_id' => $warehouse->id,
            'articles' => [
                [
                    'freezing_fish_id' => $fish->id,
                    'quantity' => 500,
                    'consumables' => [
                        ['consumable_type_id' => $cartonType->id, 'quantity' => 25],
                    ]
                ]
            ]
        ]);

        $response->assertStatus(422);
        $this->assertEquals(20.00, ConsumableStock::where('consumable_type_id', $cartonType->id)->value('current_quantity'));
    }

    #[Test]
    public function test_9_workforce_calculation()
    {
        $user = User::first();
        $workerA = Workforce::where('name', 'Worker A')->first();
        $workerB = Workforce::where('name', 'Worker B')->first();

        // Worker A: Oct 1 = 150 MAD, Oct 2 = 170 MAD
        $this->actingAs($user)->postJson('/api/workforce-assignments', [
            'workforce_ids' => [$workerA->id],
            'dates' => ['2026-10-01'],
            'daily_rate' => 150,
        ]);
        $this->actingAs($user)->postJson('/api/workforce-assignments', [
            'workforce_ids' => [$workerA->id],
            'dates' => ['2026-10-02'],
            'daily_rate' => 170,
        ]);

        // Worker B: Oct 1 = 180 MAD
        $this->actingAs($user)->postJson('/api/workforce-assignments', [
            'workforce_ids' => [$workerB->id],
            'dates' => ['2026-10-01'],
            'daily_rate' => 180,
        ]);

        $summaryRes = $this->actingAs($user)->getJson('/api/workforce-assignments/monthly-summary?year=2026&month=10');
        $summaryRes->assertStatus(200);

        $workersData = collect($summaryRes->json('workers'));
        $dataA = $workersData->firstWhere('workforce_id', $workerA->id);
        $dataB = $workersData->firstWhere('workforce_id', $workerB->id);

        $this->assertEquals(320.00, $dataA['total_amount']);
        $this->assertEquals(180.00, $dataB['total_amount']);
        $this->assertEquals(500.00, $summaryRes->json('grand_total'));
    }
}
