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
        Schema::create('order_picking_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_detail_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('ordered_qty');
            $table->integer('picked_qty')->default(0);
            $table->integer('missing_qty')->default(0);
            $table->enum('missing_reason', ['out_of_stock', 'damaged', 'expired', 'not_found'])->nullable();
            $table->unsignedBigInteger('picked_by')->nullable();
            $table->timestamp('picked_at')->nullable();
            $table->enum('status', ['pending', 'picked', 'partial', 'missing'])->default('pending');
            $table->timestamps();

            // Indexes
            $table->index('order_id');
            $table->index('order_detail_id');
            $table->index('product_id');

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('order_detail_id')->references('id')->on('order_details')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('picked_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_picking_items');
    }
};
