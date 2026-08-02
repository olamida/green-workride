<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mutual ride ratings: each party to a booking rates the other once.
        // Driver score = average rating received on completed trips. Ratings
        // are double-sided (driver ⇄ passenger) per the guide's driver_scores.
        Schema::create('ride_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('rater_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ratee_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'rater_id']);
            $table->index(['ratee_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_ratings');
    }
};
