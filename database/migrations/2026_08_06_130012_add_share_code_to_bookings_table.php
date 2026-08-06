<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Share-to-join (Sprint 3 §3.4): records which public ride-code/link a seat
 * request came through. Referral attribution itself already lives on
 * `bookings.referred_by_user_id`; this column is the audit link back to the
 * shared trip's `share_code`, so Ops can tell which link produced requests.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('share_code', 32)->nullable()->after('referred_by_user_id');
            $table->index('share_code');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['share_code']);
            $table->dropColumn('share_code');
        });
    }
};
