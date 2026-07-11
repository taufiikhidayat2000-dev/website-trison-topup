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
        Schema::table('p_p_o_b_products', function (Blueprint $table) {
            $table->foreignId('p_p_o_b_product_category_id')
                ->nullable()
                ->after('p_p_o_b_brand_id')
                ->constrained('p_p_o_b_product_categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('p_p_o_b_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('p_p_o_b_product_category_id');
        });
    }
};
