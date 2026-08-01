<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('role', 30)->default('passenger')->index()->after('avatar');
            $table->unsignedTinyInteger('verification_level')->default(0)->index()->after('role');
            $table->foreignId('workplace_id')->nullable()->after('verification_level');
            $table->string('nin_hash')->nullable()->after('workplace_id');
            $table->string('nin_last4', 4)->nullable()->after('nin_hash');
            $table->boolean('is_banned')->default(false)->after('nin_last4');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'avatar', 'role', 'verification_level',
                'workplace_id', 'nin_hash', 'nin_last4', 'is_banned',
            ]);
        });
    }
};
