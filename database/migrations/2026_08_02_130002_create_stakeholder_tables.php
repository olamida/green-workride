<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NURTW/RTEAN chapters and parks. Don't fight the unions — make them
        // agents: their park is the official hub, they get a 5% remittance.
        Schema::create('unions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('park_location')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('corridor')->nullable()->index();
            $table->decimal('commission_rate', 5, 2)->default(0.05);
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Per-trip union share: amount = fare * union commission. Created at
        // trip completion, settled via Moniepoint daily.
        Schema::create('stakeholder_remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->foreignId('union_id')->constrained('unions')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending'); // pending | paid
            $table->string('reference')->unique();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['union_id', 'status']);
            $table->index(['trip_id', 'status']);
        });

        // Regulatory/insurance permits with expiry reminders (guide §15): the
        // "Staff Mobility Cooperative" registration + vehicle papers + insurance.
        Schema::create('permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('permit_type'); // cooperative | commercial_vehicle | insurance | safety
            $table->string('permit_number')->nullable();
            $table->string('issuer')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at');
            $table->timestamps();

            $table->index(['permit_type', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permits');
        Schema::dropIfExists('stakeholder_remittances');
        Schema::dropIfExists('unions');
    }
};
