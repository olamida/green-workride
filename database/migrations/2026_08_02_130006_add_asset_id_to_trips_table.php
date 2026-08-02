<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Link every published trip to the fleet asset that serves it (guide §11).
        // The publish gate blocks drivers whose assigned asset is grounded or
        // failed today's pre-trip inspection.
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('asset_id')->nullable()->after('vehicle_id')->constrained('assets')->nullOnDelete();
            $table->index(['asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_id');
            $table->dropIndex(['asset_id', 'status']);
        });
    }
};
