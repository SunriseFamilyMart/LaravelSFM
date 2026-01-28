<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');

            // payment_method: can be 'upi' or 'credit_sale'
            $table->enum('payment_method', ['upi', 'credit_sale'])->nullable();

            // For UPI
            $table->string('transaction_id')->nullable();

            // For Credit Sale
            $table->decimal('first_payment', 10, 2)->nullable();
            $table->decimal('second_payment', 10, 2)->nullable();
            $table->date('first_payment_date')->nullable();
            $table->date('second_payment_date')->nullable();

            // Payment status: 'complete' or 'incomplete'
            $table->enum('payment_status', ['complete', 'incomplete'])->default('incomplete');

            $table->timestamps();

            // Foreign key constraint (optional)
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
