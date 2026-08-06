<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pre-due reminder idempotency: a credit is reminded at most once per
        // due date. The job sets this when it sends, so reruns never re-send.
        Schema::table('ride_credits', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('ride_credits', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
