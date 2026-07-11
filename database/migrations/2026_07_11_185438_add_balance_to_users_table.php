<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Integer Rupiah, matching the money-column convention used across
            // orders/payments/products (no decimals/floats for money).
            $table->unsignedBigInteger('balance')->default(0)->after('phone');
            $table->boolean('is_active')->default(true)->after('balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['balance', 'is_active']);
        });
    }
};
