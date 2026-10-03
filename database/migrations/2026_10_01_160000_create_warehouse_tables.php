<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Providers
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_info')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 2. Clients
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_info')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 3. Freezing Fish
        Schema::create('freezing_fish', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 4. Consumable Types
        Schema::create('consumable_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit')->default('pcs');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 5. Fish Warehouses
        Schema::create('fish_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 6. Containers
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('capacity', 12, 2);
            $table->string('unit')->default('kg');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 7. Voucher Types
        Schema::create('voucher_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // reception, stock_out, consumable_receipt
            $table->enum('effect', ['stock_in', 'stock_out', 'consumable_stock_in']);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 8. Workforces
        Schema::create('workforces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('identifier')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 9. Vouchers
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->unique();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->onDelete('cascade');
            $table->foreignId('fish_warehouse_id')->nullable()->constrained('fish_warehouses')->onDelete('set null');
            $table->date('voucher_date');
            $table->string('truck_licence')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('confirmed');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // Voucher Provider Pivot
        Schema::create('voucher_provider', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
        });

        // Voucher Client Pivot
        Schema::create('voucher_client', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
        });

        // 10. Fish Stocks (Active Stock Lots)
        Schema::create('fish_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
            $table->foreignId('freezing_fish_id')->constrained('freezing_fish')->onDelete('cascade');
            $table->foreignId('fish_warehouse_id')->constrained('fish_warehouses')->onDelete('cascade');
            $table->foreignId('container_id')->nullable()->constrained('containers')->onDelete('set null');
            $table->date('reception_date');
            $table->string('provider_names')->nullable();
            $table->decimal('original_quantity', 12, 2);
            $table->decimal('remaining_quantity', 12, 2);
            $table->decimal('calculated_boxes', 12, 2)->default(0);
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
        });

        // 11. Voucher Article Details
        Schema::create('voucher_article_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
            $table->foreignId('freezing_fish_id')->constrained('freezing_fish')->onDelete('cascade');
            $table->foreignId('container_id')->nullable()->constrained('containers')->onDelete('set null');
            $table->foreignId('fish_warehouse_id')->constrained('fish_warehouses')->onDelete('cascade');
            $table->decimal('quantity', 12, 2);
            $table->decimal('calculated_boxes', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->foreignId('fish_stock_id')->nullable()->constrained('fish_stocks')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 12. Article Consumable Details
        Schema::create('article_consumable_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_article_detail_id')->constrained('voucher_article_details')->onDelete('cascade');
            $table->foreignId('consumable_type_id')->constrained('consumable_types')->onDelete('cascade');
            $table->decimal('quantity', 12, 2);
            $table->string('unit')->default('pcs');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // 13. Archived Fish Stocks
        Schema::create('archived_fish_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_stock_id');
            $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
            $table->foreignId('voucher_article_detail_id')->nullable()->constrained('voucher_article_details')->onDelete('set null');
            $table->foreignId('freezing_fish_id')->constrained('freezing_fish')->onDelete('cascade');
            $table->foreignId('fish_warehouse_id')->constrained('fish_warehouses')->onDelete('cascade');
            $table->foreignId('container_id')->nullable()->constrained('containers')->onDelete('set null');
            $table->decimal('original_quantity', 12, 2);
            $table->decimal('final_quantity', 12, 2)->default(0);
            $table->date('reception_date');
            $table->string('provider_names')->nullable();
            $table->timestamp('archived_at')->useCurrent();
            $table->timestamps();
        });

        // 14. Consumable Stocks
        Schema::create('consumable_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_type_id')->unique()->constrained('consumable_types')->onDelete('cascade');
            $table->decimal('total_received', 12, 2)->default(0);
            $table->decimal('total_consumed', 12, 2)->default(0);
            $table->decimal('current_quantity', 12, 2)->default(0);
            $table->string('unit')->default('pcs');
            $table->timestamps();
        });

        // 15. Consumable Receipt Details
        Schema::create('consumable_receipt_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
            $table->foreignId('consumable_type_id')->constrained('consumable_types')->onDelete('cascade');
            $table->decimal('quantity', 12, 2);
            $table->string('unit')->default('pcs');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // 16. Workforce Assignments
        Schema::create('workforce_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workforce_id')->constrained('workforces')->onDelete('cascade');
            $table->date('date');
            $table->decimal('daily_rate', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['workforce_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workforce_assignments');
        Schema::dropIfExists('consumable_receipt_details');
        Schema::dropIfExists('consumable_stocks');
        Schema::dropIfExists('archived_fish_stocks');
        Schema::dropIfExists('article_consumable_details');
        Schema::dropIfExists('voucher_article_details');
        Schema::dropIfExists('fish_stocks');
        Schema::dropIfExists('voucher_client');
        Schema::dropIfExists('voucher_provider');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('workforces');
        Schema::dropIfExists('voucher_types');
        Schema::dropIfExists('containers');
        Schema::dropIfExists('fish_warehouses');
        Schema::dropIfExists('consumable_types');
        Schema::dropIfExists('freezing_fish');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('providers');
    }
};
