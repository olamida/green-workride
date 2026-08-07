<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 4 — trip provenance for the recurring engines.
     *
     *   schedule_ref  — the BusSchedule that materialised this trip, formatted
     *                   SCHED-{id}-{Ymd}-{Hi}. Idempotency key: the nightly job
     *                   skips a time-slot whose reference already exists.
     *   repeat_group  — a carpool "Repeat Mon–Fri" publish created several Trip
     *                   rows in one action; the group ref makes re-submitting
     *                   the form a no-op instead of a duplicate.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('schedule_ref', 40)->nullable()->index();
            $table->string('repeat_group', 40)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['schedule_ref', 'repeat_group']);
        });
    }
};
