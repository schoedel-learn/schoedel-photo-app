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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('photo_id')->nullable()->constrained('photos')->onDelete('set null');
            $table->string('product_type'); // e.g., 'photo', 'print', 'digital_download', etc.
            $table->json('product_details')->nullable(); // flexible JSON for product-specific data
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();

            // Indexes for common query patterns
            $table->index('order_id');
            $table->index('photo_id');
            $table->index('product_type');
            $table->index(['order_id', 'photo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

