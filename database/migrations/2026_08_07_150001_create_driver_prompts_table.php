<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->string('corridor', 20);
            $table->unsignedInteger('people_count')->default(0);
            $table->string('time_band', 30)->nullable();
            $table->string('status', 20)->default('prompted');
            $table->string('reference', 100)->unique();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index('driver_id');
            $table->index(['driver_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_prompts');
    }
};
