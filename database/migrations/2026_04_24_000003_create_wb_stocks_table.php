<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_stocks', function (Blueprint $table) {
            $table->id();
            $table->char('source_key', 64)->unique();
            $table->date('date')->nullable();
            $table->dateTime('last_change_date')->nullable();
            $table->string('supplier_article')->nullable();
            $table->string('tech_size')->nullable();
            $table->string('barcode', 64)->nullable();
            $table->integer('quantity')->nullable();
            $table->boolean('is_supply')->nullable();
            $table->boolean('is_realization')->nullable();
            $table->integer('quantity_full')->nullable();
            $table->string('warehouse_name')->nullable();
            $table->integer('in_way_to_client')->nullable();
            $table->integer('in_way_from_client')->nullable();
            $table->bigInteger('nm_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->string('sc_code')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('discount', 8, 2)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['date', 'warehouse_name']);
            $table->index('nm_id');
            $table->index('barcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_stocks');
    }
};
