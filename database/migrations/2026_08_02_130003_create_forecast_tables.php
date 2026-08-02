<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Demand forecast events (guide §9). Abuja demand follows religion,
        // government cycles (FAAC/FEC), festivals, weather and fuel scarcity.
        // Admin logs known events; the app suggests extra vehicles.
        Schema::create('forecasts', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('event_type'); // church | mosque | govt | festive | weather | fuel_scarcity
            $table->string('event_name');
            $table->string('corridor')->nullable()->index();
            $table->decimal('expected_demand_multiplier', 5, 2)->default(1.0);
            $table->unsignedInteger('recommended_extra_vehicles')->default(0);
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'corridor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecasts');
    }
};
