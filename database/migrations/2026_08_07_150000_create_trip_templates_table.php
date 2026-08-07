<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('corridor', 20);
            $table->string('route_name', 255)->nullable();
            $table->string('origin_text', 255)->nullable();
            $table->string('destination_text', 255)->nullable();
            $table->string('departure_time', 5);
            $table->json('days')->nullable();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->unsignedTinyInteger('total_seats')->default(4);
            $table->decimal('fare_per_seat', 15, 2)->nullable();
            $table->boolean('is_free_volunteer')->default(false);
            $table->boolean('women_only')->default(false);
            $table->json('waypoints')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('driver_id');
            $table->index(['driver_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_templates');
    }
};
