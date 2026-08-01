<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('employer_id')
                ->nullable()
                ->after('payment_method')
                ->constrained()
                ->nullOnDelete();
            $table->decimal('employer_contribution', 15, 2)->default(0)->after('fare_paid');
            // full | one_way | percent | capped
            $table->string('employer_coverage')->nullable()->after('employer_contribution');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employer_id');
            $table->dropColumn(['employer_contribution', 'employer_coverage']);
        });
    }
};
