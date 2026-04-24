<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_incomes', function (Blueprint $table) {
            $table->id();
            $table->char('source_key', 64)->unique();
            $table->unsignedBigInteger('income_id')->nullable();
            $table->string('number')->nullable();
            $table->date('date')->nullable();
            $table->dateTime('last_change_date')->nullable();
            $table->string('supplier_article')->nullable();
            $table->string('tech_size')->nullable();
            $table->string('barcode', 64)->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->date('date_close')->nullable();
            $table->string('warehouse_name')->nullable();
            $table->bigInteger('nm_id')->nullable();
            $table->longText('payload')->nullable();
            $table->timestamps();

            $table->index(['date', 'warehouse_name']);
            $table->index('income_id');
            $table->index('nm_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_incomes');
    }
};
