<?php

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
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            // Numeric-only partner_reff sent to LinkQu (see LinkQuService::generatePartnerReff()).
            $table->string('reference')->unique();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('fee');
            $table->unsignedBigInteger('total_pay');
            $table->string('channel');
            $table->string('payment_type')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_code')->nullable();
            $table->string('status')->default('pending');
            $table->string('linkqu_reference')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
