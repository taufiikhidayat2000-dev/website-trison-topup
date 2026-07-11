<?php

use App\Models\Order\Order;
use App\Models\User;
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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Order::class)->unique()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
            // Snapshots at the time of review, so history survives renames/deletion of the brand/product.
            $table->string('customer_name');
            $table->string('game_name');
            $table->string('product_name');
            $table->unsignedTinyInteger('rating');
            $table->string('review', 500);
            $table->enum('status', ['published', 'hidden', 'pending'])->default('published');
            $table->text('admin_reply')->nullable();
            $table->dateTime('admin_replied_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
