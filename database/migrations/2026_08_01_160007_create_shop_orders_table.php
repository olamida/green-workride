<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            // [{commodity_id, symbol, name, unit, quantity, unit_price_ngn}]
            $table->json('items');
            $table->decimal('total_ngn', 15, 2);
            // cash | earned — subsidy credits can never buy goods (ride-only).
            $table->string('paid_from');
            // placed | fulfilled | cancelled
            $table->string('status')->default('placed');
            $table->json('meta')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_orders');
    }
};
