<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Passenger interest registration (section 2.2): "I want this journey".
        // A soft signal, NOT a booking — it never touches seats or money, and
        // keeps the unique (trip_id, passenger_id) seat invariant on bookings
        // intact. Ops reads it as supply planning ("12 people want Kubwa→CBD").
        Schema::create('trip_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // pending | notified | matched
            $table->string('status')->default('pending')->index();
            $table->timestamp('registered_at');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();

            $table->unique(['trip_id', 'user_id']);
            $table->index(['status', 'registered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_interests');
    }
};
