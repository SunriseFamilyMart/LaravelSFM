<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pr_orders', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('sales_person_id')->nullable();

            $table->boolean('is_guest')->default(0);

            $table->decimal('order_amount', 8, 2)->default(0.00);
            $table->decimal('coupon_discount_amount', 8, 2)->default(0.00);
            $table->string('coupon_discount_title')->nullable();

            $table->string('payment_status')->default('unpaid');
            $table->string('order_status')->default('pending');

            $table->decimal('total_tax_amount', 8, 2)->default(0.00);

            $table->string('payment_method', 30)->nullable();
            $table->string('transaction_reference', 30)->nullable();

            $table->unsignedBigInteger('delivery_address_id')->nullable();
            $table->boolean('checked')->default(0);
            $table->unsignedBigInteger('delivery_man_id')->nullable();

            $table->string('trip_number')->nullable();
            $table->decimal('delivery_charge', 8, 2)->default(0.00);

            $table->text('order_note')->nullable();
            $table->string('coupon_code')->nullable();

            $table->string('order_type')->default('delivery');
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->unsignedBigInteger('time_slot_id')->nullable();

            $table->date('date')->nullable();
            $table->date('delivery_date')->nullable();

            $table->string('callback')->nullable();
            $table->decimal('extra_discount', 8, 2)->default(0.00);

            $table->text('delivery_address')->nullable();
            $table->string('payment_by')->nullable();
            $table->string('payment_note')->nullable();

            $table->double('free_delivery_amount', 8, 2)->default(0.00);
            $table->double('weight_charge_amount', 8, 2)->default(0.00);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pr_orders');
    }
};
