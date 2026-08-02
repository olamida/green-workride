<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_cost_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // KYC purpose (nin_check, driver_liveness) — audit + per-purpose caps.
            $table->string('purpose', 40)->nullable();
            // Idempotency key so a retried check can never double-charge.
            $table->string('reference', 80)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('api_cost_logs', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['purpose', 'reference']);
        });
    }
};
