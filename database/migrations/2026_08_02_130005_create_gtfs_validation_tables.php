<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Google Transit partner program validation history — each regeneration
        // records feedvalidator.mobilitydata.org outcome (guide §12).
        Schema::create('gtfs_validations', function (Blueprint $table) {
            $table->id();
            $table->string('feed_path');
            $table->string('validator_version')->nullable();
            $table->string('status')->default('pending'); // pending | passed | failed
            $table->unsignedInteger('errors_count')->default(0);
            $table->unsignedInteger('warnings_count')->default(0);
            $table->string('report_path')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtfs_validations');
    }
};
