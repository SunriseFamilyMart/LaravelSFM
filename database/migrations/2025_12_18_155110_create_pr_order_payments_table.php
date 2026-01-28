<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pr_order_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pr_order_id');

            $table->enum('payment_method', ['cash','upi','credit_sale','other'])->nullable();
            $table->decimal('amount', 10, 2)->nullable();

            $table->date('payment_date')->nullable();
            $table->string('transaction_id')->nullable();

            $table->decimal('first_payment', 10, 2)->nullable();
            $table->decimal('second_payment', 10, 2)->nullable();

            $table->date('first_payment_date')->nullable();
            $table->date('second_payment_date')->nullable();

            $table->enum('payment_status', ['complete','incomplete'])->default('incomplete');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pr_order_payments');
    }
};
