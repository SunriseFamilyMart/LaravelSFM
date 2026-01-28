<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('adminpurchase', function (Blueprint $table) {
            $table->id();

            $table->string('purchase_id')->unique(); // PR-1, PR-2
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('product_id');

            $table->string('purchased_by');
            $table->date('purchase_date');

            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();

            $table->string('invoice_number')->nullable();

            $table->enum('status', [
                'Pending',
                'In Progress',
                'Delivered',
                'Delayed'
            ])->default('Pending');

            $table->decimal('mrp', 10, 2)->default(0);
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->integer('quantity')->default(1);

            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);

            $table->enum('payment_mode', [
                'Cash',
                'UPI',
                'Bank Transfer'
            ])->nullable();

            $table->text('comments')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adminpurchase');
    }
};
