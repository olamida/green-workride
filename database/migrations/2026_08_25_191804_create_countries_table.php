<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nigeria, Kenya, Ghana, Uganda
            $table->string('iso_code', 3)->unique(); // NG, KE, GH, UG
            $table->string('currency_code', 3); // NGN, KES, GHS, UGX
            $table->string('currency_symbol', 5); // ₦, KSh, GH₵, USh
            $table->string('phone_prefix', 10); // +234, +254, +233, +256
            $table->string('timezone', 50); // Africa/Lagos, Africa/Nairobi
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
