<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            // drivers | passengers | volunteers | both
            $table->string('audience');
            // trip_completed | volunteer_ride | weekly_five_rides | monthly_ten_rides | pothole_confirmed
            $table->string('trigger');
            // cash | earned | subsidy | green_points
            $table->string('reward_type');
            $table->decimal('reward_value', 15, 2);
            // once | daily | weekly | monthly | unlimited
            $table->string('period')->default('once');
            $table->decimal('budget_total', 15, 2)->nullable();
            $table->decimal('budget_spent', 15, 2)->default(0);
            // government | private | community — who funds this campaign.
            $table->string('sponsor_type')->default('community');
            $table->string('sponsor_name')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('reward_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('reward_campaigns')->cascadeOnDelete();
            $table->string('trigger');
            $table->string('reward_type');
            $table->decimal('reward_value', 15, 2);
            // Idempotency key: REW-{campaign}-{user}-{periodKey}.
            $table->string('reference')->unique();
            $table->string('period_key');
            $table->json('meta')->nullable();
            $table->timestamp('awarded_at');
            $table->timestamps();

            $table->index(['user_id', 'campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_claims');
        Schema::dropIfExists('reward_campaigns');
    }
};
