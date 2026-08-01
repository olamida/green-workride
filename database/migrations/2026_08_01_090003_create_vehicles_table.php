<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plate_number')->unique();
            $table->string('make');
            $table->string('model');
            $table->string('color')->nullable();
            $table->unsignedSmallInteger('seats')->default(4);
            $table->string('type', 20)->default('sedan')->index();
            $table->boolean('papers_verified')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
