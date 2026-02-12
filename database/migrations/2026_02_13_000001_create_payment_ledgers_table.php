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
        Schema::create('payment_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('order_id')->nullable(); // Can be null for store-level payments
            $table->enum('entry_type', ['CREDIT', 'DEBIT']); // CREDIT = money received, DEBIT = refund/credit note
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 50)->nullable(); // cash, upi, bank, cheque, credit_note, etc.
            $table->string('transaction_ref', 255)->nullable(); // Transaction ID or reference
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('store_id');
            $table->index('order_id');
            $table->index('entry_type');
            $table->index('payment_method');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_ledgers');
    }
};
