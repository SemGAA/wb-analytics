<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_sales', function (Blueprint $table) {
            $table->id();
            $table->char('source_key', 64)->unique();
            $table->string('g_number')->nullable();
            $table->date('date')->nullable();
            $table->dateTime('last_change_date')->nullable();
            $table->string('supplier_article')->nullable();
            $table->string('tech_size')->nullable();
            $table->string('barcode', 64)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->decimal('discount_percent', 8, 2)->nullable();
            $table->boolean('is_supply')->nullable();
            $table->boolean('is_realization')->nullable();
            $table->decimal('promo_code_discount', 8, 2)->nullable();
            $table->string('warehouse_name')->nullable();
            $table->string('country_name')->nullable();
            $table->string('oblast_okrug_name')->nullable();
            $table->string('region_name')->nullable();
            $table->unsignedBigInteger('income_id')->nullable();
            $table->string('sale_id', 64)->nullable();
            $table->string('odid', 64)->nullable();
            $table->decimal('spp', 8, 2)->nullable();
            $table->decimal('for_pay', 15, 2)->nullable();
            $table->decimal('finished_price', 15, 2)->nullable();
            $table->decimal('price_with_disc', 15, 2)->nullable();
            $table->bigInteger('nm_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->boolean('is_storno')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['date', 'warehouse_name']);
            $table->index('nm_id');
            $table->index('sale_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_sales');
    }
};
