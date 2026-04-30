<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('dealer_id')->constrained('users')->cascadeOnDelete();
            $table->date('order_date');
            $table->enum('label_status', ['pending', 'printed', 'attached'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->enum('dispatch_status', ['pending', 'partial', 'sent', 'delivered'])->default('pending');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('token_amount', 12, 2)->default(0);
            $table->decimal('amount_received', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->date('full_amount_date')->nullable();
            $table->decimal('total_received', 12, 2)->default(0);
            $table->date('total_received_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'dealer_id']);
            $table->index('order_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
