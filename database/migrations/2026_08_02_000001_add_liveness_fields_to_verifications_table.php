<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            $table->unsignedInteger('liveness_score')->nullable();
            $table->unsignedInteger('face_match_score')->nullable();
            // open | identitypass | smile | dojah — which engine produced this result.
            $table->string('provider', 30)->default('open');
            // 1 | 2 | 3 — the KYC tier that created this verification (guide §7 + Sprint 3.6).
            $table->string('tier', 10)->nullable();
            $table->string('nimc_reference', 60)->nullable();
            // Encrypted selfie on the private disk; purged by DeleteExpiredSelfiesJob.
            $table->string('selfie_path')->nullable();
            $table->timestamp('selfie_retention_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            $table->dropColumn([
                'liveness_score',
                'face_match_score',
                'provider',
                'tier',
                'nimc_reference',
                'selfie_path',
                'selfie_retention_expires_at',
            ]);
        });
    }
};
