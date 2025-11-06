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
        Schema::table('downloads', function (Blueprint $table) {
            // Add unique constraint to prevent duplicate download records
            // for the same photo/order/user combination
            $table->unique(['order_id', 'photo_id', 'user_id'], 'downloads_order_photo_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            $table->dropUnique('downloads_order_photo_user_unique');
        });
    }
};
