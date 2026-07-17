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
        Schema::create('reseller_price_uses', function (Blueprint $table) {
            $table->id();
            $table->morphs('usable');
            $table->unsignedBigInteger('original_price');
            $table->unsignedBigInteger('reseller_price');
            $table->unsignedBigInteger('discount_amount');
            $table->decimal('discount_percent_snapshot', 5, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_price_uses');
    }
};
