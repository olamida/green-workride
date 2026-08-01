<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_cost_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40)->index();
            $table->string('service', 60)->index();
            $table->decimal('cost_naira', 12, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['provider', 'created_at']);
            $table->index(['service', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_cost_logs');
    }
};
