<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('soft_hold_expires_at')->nullable()->after('status');
            $table->index('soft_hold_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['soft_hold_expires_at']);
            $table->dropColumn('soft_hold_expires_at');
        });
    }
};
