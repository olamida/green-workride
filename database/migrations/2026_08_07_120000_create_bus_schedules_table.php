<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 4 — bus scheduling (guide §6 Workflow 5 + Citymapper-style
     * "every 15 mins 6:30–9am"). A schedule is the recurring supply backbone:
     * one row says "Kubwa→CBD every 15 min Mon–Fri 06:30–09:00", and the
     * nightly job materialises real Trip rows for today + tomorrow so the
     * normal board/booking/GTFS machinery all just works.
     */
    public function up(): void
    {
        Schema::create('bus_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('gtfs_routes');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->foreignId('driver_id')->constrained('users');
            $table->time('departure_time');                       // 06:30
            $table->time('end_time')->nullable()->comment('null = single departure'); // 09:00
            $table->integer('frequency_minutes')->default(15);    // 15 peak, 30 off-peak
            $table->json('days_of_week');                          // ["mon","tue",...]
            $table->string('status', 20)->default('active');       // active | paused
            $table->foreignId('workplace_id')->nullable()->constrained('workplaces')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_schedules');
    }
};
