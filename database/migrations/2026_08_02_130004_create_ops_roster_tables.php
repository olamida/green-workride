<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Duty roster: which driver + asset covers which corridor when (guide §7).
        Schema::create('duty_rosters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date')->index();
            $table->string('corridor')->nullable()->index();
            $table->string('status')->default('draft'); // draft | published | active | completed
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Individual shift inside a roster.
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duty_roster_id')->nullable()->constrained('duty_rosters')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('corridor')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('scheduled'); // scheduled | active | completed | cancelled
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'starts_at']);
            $table->index(['corridor', 'starts_at']);
        });

        // Weekly driver score snapshot (ratings + punctuality + pothole reports
        // + green points → a 0-100 score with a level band).
        Schema::create('driver_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('rides_completed')->default(0);
            $table->decimal('on_time_rate', 5, 2)->default(0);
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('pothole_reports')->default(0);
            $table->unsignedInteger('green_points_earned')->default(0);
            $table->unsignedInteger('score')->default(0);
            $table->string('level')->default('bronze'); // bronze | silver | gold | platinum
            $table->timestamps();

            $table->unique(['user_id', 'period_start']);
            $table->index(['period_start', 'period_end']);
        });

        // Fuel advance ledger: driver takes cash for fuel, repays from earnings.
        Schema::create('fuel_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending'); // pending | approved | paid | repaid
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('repaid_at')->nullable();
            $table->string('reference')->unique();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Monthly MDA subsidy report snapshot (audit trail for the Trust).
        Schema::create('subsidy_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workplace_id')->constrained('workplaces')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('staff_funded')->default(0);
            $table->unsignedInteger('rides_funded')->default(0);
            $table->decimal('subsidy_issued', 15, 2)->default(0);
            $table->decimal('subsidy_spent', 15, 2)->default(0);
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['workplace_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsidy_reports');
        Schema::dropIfExists('fuel_advances');
        Schema::dropIfExists('driver_scores');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('duty_rosters');
    }
};
