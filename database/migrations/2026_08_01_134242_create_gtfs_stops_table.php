<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gtfs_stops', function (Blueprint $table) {
            $table->id();
            $table->string('stop_id')->unique();
            $table->string('stop_name');
            $table->decimal('stop_lat', 10, 7);
            $table->decimal('stop_lon', 10, 7);
            $table->string('corridor', 30)->nullable()->index();
            $table->string('zone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gtfs_stops');
    }
};
