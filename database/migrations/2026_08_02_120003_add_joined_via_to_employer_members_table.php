<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tracks how a member joined so the Control Tower can tell self-serve
        // requests (pending → admin approve) from employer-uploaded rosters.
        // 'self' | 'employer'
        Schema::table('employer_members', function (Blueprint $table) {
            $table->string('joined_via')->default('employer')->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('employer_members', function (Blueprint $table) {
            $table->dropColumn('joined_via');
        });
    }
};
