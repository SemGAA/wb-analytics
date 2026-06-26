<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('external_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'external_id']);
        });

        Schema::create('api_services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->string('base_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('token_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('api_service_token_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('token_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['api_service_id', 'token_type_id']);
        });

        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('token_type_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->text('credentials');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['account_id', 'api_service_id', 'token_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
        Schema::dropIfExists('api_service_token_types');
        Schema::dropIfExists('token_types');
        Schema::dropIfExists('api_services');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('companies');
    }
};
