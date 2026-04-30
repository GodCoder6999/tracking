<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('dealer_cost', 12, 2)->nullable()->after('rate');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->text('shipping_address')->nullable()->after('notes');
            $table->text('billing_address')->nullable()->after('shipping_address');
            $table->string('payment_method', 30)->nullable()->after('billing_address');
            $table->string('invoicing_terms', 30)->nullable()->after('payment_method');
            $table->string('shipping_method', 30)->nullable()->after('invoicing_terms');
            $table->date('requested_delivery_date')->nullable()->after('shipping_method');
            $table->text('internal_notes')->nullable()->after('requested_delivery_date');
            $table->string('attachment_path')->nullable()->after('internal_notes');
            $table->decimal('gst_amount', 12, 2)->default(0)->after('attachment_path');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('gst_amount');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('dealer_cost', 12, 2)->nullable()->after('product_id');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('rate');
            $table->decimal('gst_rate', 5, 2)->default(0)->after('discount_percent');
            $table->decimal('gst_amount', 12, 2)->default(0)->after('gst_rate');
        });
    }

    public function down(): void
    {
        // SQLite doesn't support DROP COLUMN; handled by recreating in down if needed
    }
};
