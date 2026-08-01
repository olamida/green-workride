<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('road_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('type', 20)->index();
            $table->unsignedTinyInteger('severity')->default(1);
            $table->decimal('speed', 6, 2)->nullable();
            $table->decimal('accelerometer_z', 8, 2)->nullable();
            $table->boolean('is_confirmed')->default(false)->index();
            $table->string('road_name')->nullable();
            $table->timestamps();

            $table->index(['lat', 'lng']);
            $table->index(['type', 'is_confirmed', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('road_events');
    }
};
