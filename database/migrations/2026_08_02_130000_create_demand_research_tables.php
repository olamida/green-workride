<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Known waiting points (Berger, Banex, Kubwa Junction, Nyanya Under-Bridge,
        // Lugbe). Surveyors and the rider check-in reference these by id.
        Schema::create('junctions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('corridor')->nullable()->index();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('zone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        // Manual junction counts (BRT pre-design Method 1 — like LAMATA 2008).
        // "Berger 6:30-7:30am = 320 people waiting, 80% to CBD."
        Schema::create('demand_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('junction_id')->constrained('junctions')->cascadeOnDelete();
            $table->unsignedInteger('count')->default(0);
            $table->string('destination_text')->nullable();
            // 0-23 hour of the count.
            $table->unsignedTinyInteger('hour')->default(0);
            $table->string('day_type')->default('weekday'); // weekday | weekend
            $table->string('weather')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->index(['junction_id', 'day_type', 'hour']);
        });

        // Automatic demand signal from probe cars: where vehicles crawl
        // (speed < 5 km/h for > 2 min) people are waiting. Aggregated over time.
        Schema::create('probe_demand_points', function (Blueprint $table) {
            $table->id();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('corridor')->nullable()->index();
            $table->decimal('avg_speed', 6, 2)->default(0);
            $table->unsignedInteger('dwell_time_seconds')->default(0);
            $table->unsignedInteger('times_visited')->default(1);
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();

            $table->index(['lat', 'lng']);
        });

        // Workplace OD survey (guide §9B Method 3): where staff live, when they
        // travel, what they pay — becomes routes.txt + calendar.txt.
        Schema::create('od_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workplace_id')->constrained('workplaces')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('home_area');
            $table->time('departure_time')->nullable();
            $table->time('arrival_time')->nullable();
            $table->decimal('fare_paid', 15, 2)->nullable();
            $table->string('mode')->default('bus'); // bus | keke | taxi | private_car | walk
            $table->timestamps();

            $table->index(['workplace_id', 'home_area']);
        });

        // Crowdsourced demand check-in (guide §9B Method 5): a rider taps
        // "I'm at Berger, need a ride to Secretariat, 2 people" — supply planning.
        Schema::create('demand_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('pickup_lat', 10, 7);
            $table->decimal('pickup_lng', 10, 7);
            $table->string('destination_text');
            $table->unsignedTinyInteger('passengers_count')->default(1);
            $table->timestamp('requested_at')->default(now());
            $table->string('status')->default('pending'); // pending | matched | fulfilled | cancelled
            $table->foreignId('matched_trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'requested_at']);
        });

        // Origin-Destination matrix snapshot (from od_surveys + bookings).
        Schema::create('od_matrix', function (Blueprint $table) {
            $table->id();
            $table->string('origin_area');
            $table->string('destination_area');
            $table->unsignedInteger('count')->default(0);
            $table->string('corridor')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['origin_area', 'destination_area']);
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('od_matrix');
        Schema::dropIfExists('demand_requests');
        Schema::dropIfExists('od_surveys');
        Schema::dropIfExists('probe_demand_points');
        Schema::dropIfExists('demand_surveys');
        Schema::dropIfExists('junctions');
    }
};
