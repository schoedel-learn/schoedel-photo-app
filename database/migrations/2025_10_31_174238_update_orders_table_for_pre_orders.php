<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add pre_order to order_type enum
        DB::statement("ALTER TABLE orders MODIFY COLUMN order_type ENUM(
            'photo_purchase',
            'print_order',
            'digital_download',
            'session_fee',
            'subscription',
            'swag',
            'pre_order'
        ) NOT NULL");

        // Add pre-order statuses to status enum
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'pending',
            'processing',
            'completed',
            'cancelled',
            'refunded',
            'pre_order_pending',
            'pre_order_paid',
            'pre_order_selecting',
            'pre_order_finalized'
        ) NOT NULL DEFAULT 'pending'");

        // Add gallery_id for linking pre-orders to galleries
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('gallery_id')->nullable()->after('photographer_id')
                ->constrained('galleries')->onDelete('set null');
            $table->foreignId('package_id')->nullable()->after('gallery_id')
                ->constrained('packages')->onDelete('set null');
            $table->unsignedInteger('selected_photo_count')->default(0)->after('total');
            $table->decimal('upsell_amount', 10, 2)->default(0)->after('selected_photo_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['gallery_id']);
            $table->dropForeign(['package_id']);
            $table->dropColumn(['gallery_id', 'package_id', 'selected_photo_count', 'upsell_amount']);
        });

        // Revert to original enums (simplified - would need to check existing data)
        DB::statement("ALTER TABLE orders MODIFY COLUMN order_type ENUM(
            'photo_purchase',
            'print_order',
            'digital_download',
            'session_fee',
            'subscription',
            'swag'
        ) NOT NULL");

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'pending',
            'processing',
            'completed',
            'cancelled',
            'refunded'
        ) NOT NULL DEFAULT 'pending'");
    }
};
