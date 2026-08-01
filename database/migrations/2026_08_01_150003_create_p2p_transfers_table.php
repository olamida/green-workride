<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('p2p_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('receiver_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2)->default(0);
            $table->string('type', 20)->index();
            $table->string('reference')->unique();
            $table->string('status', 20)->default('completed')->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['sender_wallet_id', 'created_at']);
            $table->index(['receiver_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('p2p_transfers');
    }
};
