<?php

use App\Models\FlashSale\FlashSale;
use App\Models\FlashSale\FlashSaleProduct;
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
        Schema::create('flash_sale_uses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(FlashSale::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(FlashSaleProduct::class)->constrained()->cascadeOnDelete();
            $table->morphs('usable');
            $table->unsignedBigInteger('original_price');
            $table->unsignedBigInteger('flash_price');
            $table->unsignedBigInteger('discount_amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flash_sale_uses');
    }
};
