<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('route_name');
            $table->string('corridor', 30)->index();
            $table->string('origin_text');
            $table->string('destination_text');
            $table->decimal('current_lat', 10, 7)->nullable();
            $table->decimal('current_lng', 10, 7)->nullable();
            $table->unsignedSmallInteger('total_seats');
            $table->unsignedSmallInteger('available_seats');
            $table->decimal('fare_per_seat', 15, 2)->default(0);
            $table->boolean('is_free_volunteer')->default(false)->index();
            $table->string('status', 20)->default('scheduled')->index();
            $table->timestamp('departure_time')->index();
            $table->json('waypoints')->nullable();
            $table->timestamps();

            $table->index(['corridor', 'departure_time', 'status']);
            $table->index(['current_lat', 'current_lng']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
