<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fleet assets: buses, cars, OBD2 dongles (guide §11). Start lean —
        // lease 3x 18-seaters, use lease-to-own tracked here.
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_type'); // bus | car | obd2_device
            $table->string('acquisition_type')->default('lease'); // lease | owned | donated
            $table->string('vin')->nullable();
            $table->string('plate_number')->nullable()->unique();
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year')->nullable();
            $table->decimal('purchase_cost', 15, 2)->default(0);
            $table->decimal('lease_monthly', 15, 2)->nullable();
            $table->decimal('depreciation_rate', 5, 2)->default(0);
            $table->unsignedInteger('mileage')->default(0);
            $table->string('status')->default('active'); // active | in_maintenance | grounded | disposed
            $table->foreignId('assigned_driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('corridor')->nullable()->index();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'corridor']);
        });

        // Preventive maintenance: 5,000 km + monthly inspection cadence.
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('type'); // preventive_5000km | monthly_inspection
            $table->unsignedInteger('due_km')->nullable();
            $table->date('due_date');
            $table->string('status')->default('scheduled'); // scheduled | due | in_progress | done
            $table->timestamp('completed_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'status', 'due_date']);
        });

        // Daily pre-trip inspection (guide §11): checklist + photos. A failed
        // inspection grounds the vehicle via the trip-publish gate.
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->string('tyre_photo_path')->nullable();
            $table->string('oil_level')->nullable();
            $table->string('interior_photo_path')->nullable();
            $table->boolean('is_passed')->default(false);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'date']);
        });

        // Driver-reported faults. High severity opens a maintenance schedule.
        Schema::create('faults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description');
            $table->string('voice_note_path')->nullable();
            $table->unsignedTinyInteger('severity')->default(1);
            $table->string('status')->default('open'); // open | in_progress | fixed
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'status']);
        });

        // OBD2 + phone telemetry (guide §11). Fuel, speed, harsh braking,
        // engine fault codes — the phone in every car becomes the sensor.
        Schema::create('telemetry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->decimal('speed', 6, 2)->nullable();
            $table->decimal('fuel_level', 5, 2)->nullable();
            $table->string('engine_fault_code')->nullable();
            $table->boolean('harsh_braking')->default(false);
            $table->timestamp('recorded_at')->index();
            $table->timestamps();

            $table->index(['asset_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry');
        Schema::dropIfExists('faults');
        Schema::dropIfExists('inspections');
        Schema::dropIfExists('maintenance_schedules');
        Schema::dropIfExists('assets');
    }
};
