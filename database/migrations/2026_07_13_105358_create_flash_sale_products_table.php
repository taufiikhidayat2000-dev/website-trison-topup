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
        Schema::create('flash_sale_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained('flash_sales')->cascadeOnDelete();
            $table->foreignId('p_p_o_b_product_id')->constrained('p_p_o_b_products')->cascadeOnDelete();
            $table->string('pricing_type')->default('percent')->comment('percent, manual');
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->unsignedBigInteger('flash_price');
            $table->unsignedInteger('flash_stock');
            $table->unsignedInteger('sold')->default(0);
            $table->string('status')->default('active')->comment('active, sold_out, inactive');
            $table->timestamps();

            $table->unique(['flash_sale_id', 'p_p_o_b_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flash_sale_products');
    }
};
