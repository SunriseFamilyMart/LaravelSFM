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
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_ledger_id');
            $table->unsignedBigInteger('order_id');
            $table->decimal('allocated_amount', 14, 2);
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('payment_ledger_id');
            $table->index('order_id');
            
            // Foreign key constraints (optional, based on your preference)
            // $table->foreign('payment_ledger_id')->references('id')->on('payment_ledgers')->onDelete('cascade');
            // $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
