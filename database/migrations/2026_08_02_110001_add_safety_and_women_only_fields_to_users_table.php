<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Women-only preference: a rider can opt to only see / only ride on
        // women_only trips (preference, never a hard match). Gender is stored
        // as a plain nullable string so it can never leak identity checks.
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('phone');
            $table->boolean('prefers_women_only')->default(false)->after('gender');
            // Safety pack emergency contact (never shared with other riders).
            $table->string('emergency_contact_name')->nullable()->after('prefers_women_only');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'prefers_women_only',
                'emergency_contact_name',
                'emergency_contact_phone',
            ]);
        });
    }
};
