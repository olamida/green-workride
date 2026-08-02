<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // Women-only ride: only female-identifying passengers can book.
            $table->boolean('women_only')->default(false)->after('is_free_volunteer');
            $table->index(['women_only', 'status', 'departure_time']);
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndex(['women_only', 'status', 'departure_time']);
            $table->dropColumn('women_only');
        });
    }
};
