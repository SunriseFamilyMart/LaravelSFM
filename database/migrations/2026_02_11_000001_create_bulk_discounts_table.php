<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBulkDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bulk_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('min_quantity');              // e.g., 3, 5, 10, 20
            $table->decimal('discount_percent', 5, 2);   // e.g., 3.00, 5.00, 8.00, 12.00
            $table->boolean('status')->default(1);       // admin can enable/disable individual tiers
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['product_id', 'min_quantity']); // prevent duplicate tiers for same product+quantity
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bulk_discounts');
    }
}
