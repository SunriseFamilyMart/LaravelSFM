<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pr_order_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('pr_order_id')->nullable();

            $table->string('order_user', 20)->nullable();
            $table->decimal('price', 8, 2)->default(0.00);

            $table->text('product_details')->nullable();
            $table->string('variation')->nullable();

            $table->decimal('discount_on_product', 8, 2)->nullable();
            $table->string('discount_type', 20)->default('amount');

            $table->integer('quantity')->default(1);
            $table->decimal('tax_amount', 8, 2)->default(1.00);

            $table->string('variant')->nullable();
            $table->string('unit')->default('pc');

            $table->boolean('is_stock_decreased')->default(1);
            $table->unsignedBigInteger('time_slot_id')->nullable();

            $table->date('delivery_date')->nullable();
            $table->string('vat_status')->default('excluded');

            $table->string('invoice_number', 100)->nullable();
            $table->date('expected_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pr_order_details');
    }
};
