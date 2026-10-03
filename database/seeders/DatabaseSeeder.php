<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ConsumableStock;
use App\Models\ConsumableType;
use App\Models\Container;
use App\Models\FishWarehouse;
use App\Models\FreezingFish;
use App\Models\Provider;
use App\Models\User;
use App\Models\VoucherType;
use App\Models\Workforce;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Test User
        User::firstOrCreate(
            ['email' => 'test@test.com'],
            [
                'name' => 'Factory Admin',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Voucher Types
        $vTypes = [
            ['name' => 'Reception Voucher', 'code' => 'reception', 'effect' => 'stock_in'],
            ['name' => 'Stock-Out / Sales Voucher', 'code' => 'stock_out', 'effect' => 'stock_out'],
            ['name' => 'Consumable Receipt Voucher', 'code' => 'consumable_receipt', 'effect' => 'consumable_stock_in'],
        ];
        foreach ($vTypes as $vt) {
            VoucherType::firstOrCreate(['code' => $vt['code']], $vt);
        }

        // 3. Providers
        $providers = ['Provider A', 'Provider B'];
        foreach ($providers as $p) {
            Provider::firstOrCreate(['name' => $p], ['contact_info' => '+212 600 000000', 'active' => true]);
        }

        // 4. Clients
        $clients = ['Client A', 'Client B'];
        foreach ($clients as $c) {
            Client::firstOrCreate(['name' => $c], ['contact_info' => '+212 611 111111', 'active' => true]);
        }

        // 5. Freezing Fish
        $fishList = [
            ['name' => 'Sardine HGT', 'code' => 'SAR-HGT'],
            ['name' => 'Mackerel', 'code' => 'MAC-WHOLE'],
            ['name' => 'Horse Mackerel', 'code' => 'HMAC-01'],
            ['name' => 'Tuna', 'code' => 'TUN-01'],
        ];
        foreach ($fishList as $f) {
            FreezingFish::firstOrCreate(['name' => $f['name']], ['code' => $f['code'], 'active' => true]);
        }

        // 6. Consumable Types
        $consumables = [
            ['name' => 'Carton', 'unit' => 'pcs'],
            ['name' => 'Cellophane', 'unit' => 'rolls'],
            ['name' => 'Strapex', 'unit' => 'rolls'],
            ['name' => 'Paper', 'unit' => 'sheets'],
            ['name' => 'Pallet', 'unit' => 'pcs'],
        ];
        foreach ($consumables as $c) {
            $ct = ConsumableType::firstOrCreate(['name' => $c['name']], ['unit' => $c['unit'], 'active' => true]);
            ConsumableStock::firstOrCreate(
                ['consumable_type_id' => $ct->id],
                [
                    'total_received' => 500,
                    'total_consumed' => 0,
                    'current_quantity' => 500,
                    'unit' => $ct->unit,
                ]
            );
        }

        // 7. Fish Warehouses
        $warehouses = [
            ['name' => 'Freezer 1', 'description' => 'Cold Store Room 1 (-22°C)'],
            ['name' => 'Freezer 2', 'description' => 'Cold Store Room 2 (-25°C)'],
        ];
        foreach ($warehouses as $w) {
            FishWarehouse::firstOrCreate(['name' => $w['name']], ['description' => $w['description'], 'active' => true]);
        }

        // 8. Containers
        $containers = [
            ['name' => 'Caisse 24', 'capacity' => 24.00, 'unit' => 'kg'],
            ['name' => 'Caisse 20', 'capacity' => 20.00, 'unit' => 'kg'],
        ];
        foreach ($containers as $cnt) {
            Container::firstOrCreate(['name' => $cnt['name']], ['capacity' => $cnt['capacity'], 'unit' => $cnt['unit'], 'active' => true]);
        }

        // 9. Workforces
        $workers = [
            ['name' => 'Worker A', 'identifier' => 'WF-001'],
            ['name' => 'Worker B', 'identifier' => 'WF-002'],
            ['name' => 'Worker C', 'identifier' => 'WF-003'],
        ];
        foreach ($workers as $wk) {
            Workforce::firstOrCreate(['name' => $wk['name']], ['identifier' => $wk['identifier'], 'active' => true]);
        }
    }
}
