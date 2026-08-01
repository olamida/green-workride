<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodities', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->unique();
            $table->string('name');
            // precious_metal | agricultural | fuel
            $table->string('category');
            // gram | kg | bag | litre
            $table->string('unit')->default('unit');
            $table->decimal('current_price_ngn', 15, 2);
            $table->boolean('is_tradable')->default(true);
            $table->boolean('is_shop_item')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodities');
    }
};
