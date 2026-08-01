<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('rc_number')->nullable()->unique();
            $table->string('address')->nullable();
            // Origin/destination zone used to resolve one-way coverage direction.
            $table->string('zone')->nullable();
            $table->foreignId('workplace_id')->nullable()->constrained()->nullOnDelete();
            // Corporate Mobility Program policy — full | one_way | percent | capped.
            $table->string('program_type');
            $table->decimal('percent_covered', 5, 2)->default(0);
            $table->decimal('max_per_trip', 15, 2)->nullable();
            $table->decimal('max_monthly_per_employee', 15, 2)->nullable();
            $table->json('corridors')->nullable();
            $table->string('covered_direction')->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('employer_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('cash_balance', 15, 2)->default(0);
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('employer_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_wallet_id')->constrained()->cascadeOnDelete();
            // funding | cover | refund
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->string('reference')->unique();
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_transactions');
        Schema::dropIfExists('employer_wallets');
        Schema::dropIfExists('employers');
    }
};
