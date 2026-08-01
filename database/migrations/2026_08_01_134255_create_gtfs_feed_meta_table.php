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
        Schema::create('gtfs_feed_meta', function (Blueprint $table) {
            $table->id();
            $table->timestamp('last_generated_at')->nullable();
            $table->unsignedInteger('stops_count')->default(0);
            $table->unsignedInteger('routes_count')->default(0);
            $table->unsignedInteger('trips_count')->default(0);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('feed_hash', 64)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gtfs_feed_meta');
    }
};
