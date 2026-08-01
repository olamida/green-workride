<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impact_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_trips')->default(0);
            $table->decimal('co2_saved_kg', 12, 2)->default(0);
            $table->decimal('fuel_saved_litres', 12, 2)->default(0);
            $table->decimal('trees_equivalent', 10, 2)->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impact_stats');
    }
};
