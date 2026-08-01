<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('passenger_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();
            $table->string('status', 20)->default('requested')->index();
            $table->decimal('fare_paid', 15, 2)->default(0);
            $table->string('payment_method', 20)->default('wallet');
            $table->timestamps();

            $table->unique(['trip_id', 'passenger_id']);
            $table->index('passenger_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
