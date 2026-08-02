<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A promoted activity: "give 5 volunteer rides → ₦15,000". The promoter
        // defines the activity + reward; the app observes progress and pays out.
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // government | private | community — who sponsors this activity.
            $table->string('sponsor_type')->default('community');
            $table->string('sponsor_name')->nullable();
            // volunteer_rides | paid_rides | passenger_rides | peak_hour_rides |
            // pothole_reports | potholes_confirmed | custom
            $table->string('activity_type');
            $table->unsignedInteger('metric_goal')->default(1);
            $table->unsignedInteger('metric_window_days')->default(30);
            // cash | earned | subsidy | green_points (reuses RewardType semantics)
            $table->string('reward_type');
            $table->decimal('reward_value', 15, 2);
            // auto (app-measured) | proof (photo + promoter review)
            $table->string('verification_mode')->default('auto');
            $table->string('proof_label')->nullable();
            $table->text('instructions')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->decimal('budget_total', 15, 2)->nullable();
            $table->decimal('budget_spent', 15, 2)->default(0);
            // draft | published | ended
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'activity_type']);
            $table->index(['status', 'verification_mode']);
        });

        // Per-user progress toward a mission (auto mode). Rows are created on
        // first qualifying event and are unique per (user, mission).
        Schema::create('mission_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->unsignedInteger('metric_count')->default(0);
            // in_progress | achieved | awarded
            $table->string('status')->default('in_progress');
            $table->timestamp('achieved_at')->nullable();
            $table->timestamp('awarded_at')->nullable();
            // Idempotency key: MIS-{mission}-{user}-{progressId}.
            $table->string('reference')->unique()->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'mission_id']);
        });

        // Proof-based submissions (verification_mode = proof): photo + location
        // + note, reviewed by the promoter/admin before any reward is paid.
        Schema::create('mission_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->string('proof_photo_path');
            $table->text('note')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            // pending | approved | rejected
            $table->string('status')->default('pending');
            $table->boolean('reward_awarded')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['mission_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_submissions');
        Schema::dropIfExists('mission_progress');
        Schema::dropIfExists('missions');
    }
};
