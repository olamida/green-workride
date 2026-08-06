<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Learned demand forecasts (guide §9 Phase 2): the ML job trains on
        // boarded/completed bookings history and writes per-corridor, per-hour
        // predicted demand snapshots so Ops can schedule before demand spikes.
        Schema::create('demand_forecasts', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->unsignedTinyInteger('hour'); // 0-23 booking hour
            $table->string('corridor')->index();
            $table->decimal('baseline', 10, 2)->default(0); // avg same weekday+hour bookings
            $table->decimal('multiplier', 5, 2)->default(1.0); // applied event multiplier
            $table->decimal('predicted', 10, 2)->default(0); // baseline × multiplier
            $table->unsignedInteger('data_points')->default(0); // weeks with history
            $table->timestamps();

            $table->unique(['date', 'hour', 'corridor']);
            $table->index(['corridor', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_forecasts');
    }
};
