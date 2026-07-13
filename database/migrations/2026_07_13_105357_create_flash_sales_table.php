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
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('icon_type')->default('emoji')->comment('emoji, image');
            $table->string('icon_emoji')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('countdown_style')->default('detailed')->comment('detailed, compact');
            $table->boolean('auto_start')->default(true);
            $table->boolean('auto_end')->default(true);
            $table->string('after_end_action')->default('revert_price')->comment('revert_price, hide, sold_out, keep_showing');
            $table->string('status')->default('draft')->comment('draft, scheduled, active, ended, disabled');
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flash_sales');
    }
};
