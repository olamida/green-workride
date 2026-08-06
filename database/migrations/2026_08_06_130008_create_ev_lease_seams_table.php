<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // EV lease-to-own seams (WORKRIDE-DESIGN-REVIEWS §4): finance layer over
        // the existing fleet, not a parallel subsystem. Gated on FEATURE_EV_LEASE.
        Schema::table('assets', function (Blueprint $table) {
            $table->string('propulsion')->default('ice')->after('asset_type'); // ice | hybrid | ev
        });

        Schema::table('telemetry', function (Blueprint $table) {
            $table->decimal('battery_soc', 5, 2)->nullable()->after('fuel_level'); // state of charge %
            $table->decimal('range_km', 8, 2)->nullable()->after('battery_soc'); // remaining EV range
        });

        // Lease-to-own agreement: driver pays per_km_ngn × trip distance from
        // earnings until the vehicle is theirs. Fuel baseline makes the charge
        // a fuel-price hedge, not a one-way bet.
        Schema::create('lease_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('total_ngn', 15, 2);
            $table->decimal('paid_ngn', 15, 2)->default(0);
            $table->decimal('per_km_ngn', 10, 2);
            $table->decimal('fuel_baseline_ngn_per_litre', 10, 2)->nullable();
            $table->string('status')->default('active'); // active | paid_off | defaulted | cancelled
            $table->date('next_due_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'status']);
            $table->index(['driver_id', 'status']);
        });

        // Charging station catalog: where an EV can charge. Scheduler-friendly.
        Schema::create('charging_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedSmallInteger('kw')->nullable();
            $table->unsignedTinyInteger('slots')->default(1);
            $table->boolean('is_available')->default(true);
            $table->string('corridor')->nullable()->index();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charging_stations');
        Schema::dropIfExists('lease_agreements');

        Schema::table('telemetry', function (Blueprint $table) {
            $table->dropColumn(['battery_soc', 'range_km']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('propulsion');
        });
    }
};
