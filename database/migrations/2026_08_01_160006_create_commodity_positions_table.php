<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodity_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commodity_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('avg_cost_ngn', 15, 2)->default(0);
            $table->unique(['user_id', 'commodity_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_positions');
    }
};
