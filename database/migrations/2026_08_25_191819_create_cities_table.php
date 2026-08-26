<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Abuja, Nairobi, Accra, Kampala
            $table->string('slug')->unique(); // abuja, nairobi, accra, kampala
            $table->decimal('center_lat', 10, 7);
            $table->decimal('center_lng', 10, 7);
            $table->decimal('bounds_min_lat', 10, 7)->nullable();
            $table->decimal('bounds_max_lat', 10, 7)->nullable();
            $table->decimal('bounds_min_lng', 10, 7)->nullable();
            $table->decimal('bounds_max_lng', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
