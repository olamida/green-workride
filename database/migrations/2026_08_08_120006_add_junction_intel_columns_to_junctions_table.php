<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('junctions', function (Blueprint $table) {
            $table->unsignedInteger('passenger_volume_daily')->default(0)->after('zone');
            $table->boolean('is_major_hub')->default(false)->after('passenger_volume_daily');
            $table->string('state', 12)->nullable()->after('is_major_hub');
            $table->unsignedSmallInteger('avg_wait_time_mins')->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('junctions', function (Blueprint $table) {
            $table->dropColumn(['avg_wait_time_mins', 'state', 'is_major_hub', 'passenger_volume_daily']);
        });
    }
};
