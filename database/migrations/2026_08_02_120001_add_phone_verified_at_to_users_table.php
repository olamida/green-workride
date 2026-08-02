<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phone verification is the Tier-0 "instant booking" gate: an OTP proves
        // the number is live so a new rider can book at the normal fixed fare
        // before completing formal KYC. Benefits (subsidy, employer coverage,
        // ride credits, volunteer/free rides) still require Level 1+.
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('phone_verified_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_verified_at');
        });
    }
};
