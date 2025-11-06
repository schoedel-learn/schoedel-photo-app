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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photographer_id')->constrained('users')->onDelete('cascade');
            $table->string('name'); // e.g., "Basic", "Deluxe", "Premium"
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('photo_count'); // Number of photos included
            $table->boolean('includes_digital')->default(true); // Includes digital downloads
            $table->boolean('includes_prints')->default(false); // Includes physical prints
            $table->json('features')->nullable(); // Additional features (JSON array)
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('photographer_id');
            $table->index('is_active');
            $table->index(['photographer_id', 'is_active']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
