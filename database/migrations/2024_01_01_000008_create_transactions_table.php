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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('payment_gateway'); // stripe, paypal, etc.
            $table->string('gateway_transaction_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
                'refunded'
            ])->default('pending');
            $table->string('payment_method')->nullable(); // card, paypal, bank_transfer, etc.
            $table->json('metadata')->nullable(); // additional gateway-specific data
            $table->timestamps();

            // Indexes for common query patterns
            $table->index('order_id');
            $table->index('payment_gateway');
            $table->index('gateway_transaction_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['order_id', 'status']);
            $table->unique(['payment_gateway', 'gateway_transaction_id'], 'unique_gateway_transaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

