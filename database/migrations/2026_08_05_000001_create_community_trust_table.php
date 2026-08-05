<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Community Trust ledger (guide §2.1, Design Review 3): the auditable
        // float behind "Ride Now, Drive Later" (Time-Bank) and the 15% profit
        // share. Every movement is a single row with an idempotent reference
        // and a running balance, so funders/auditors can reconcile the Trust.
        Schema::create('community_trust', function (Blueprint $table) {
            $table->id();
            // credit (Trust extends value) | debit (Trust receives / float released)
            $table->string('direction');
            // time_bank_float | operations_profit_share | research_fund | community_subsidy | contingency
            $table->string('type')->index();
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('reference')->unique();
            $table->json('meta')->nullable();
            $table->timestamp('recorded_at')->index();
            $table->timestamps();

            $table->index(['type', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_trust');
    }
};
