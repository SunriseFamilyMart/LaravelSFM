<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Create purchases_master table
        Schema::create('purchases_master', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number', 50)->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->string('purchased_by', 255);
            $table->date('purchase_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->enum('status', ['draft', 'ordered', 'partial_delivered', 'delivered', 'cancelled', 'delayed'])->default('draft');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('gst_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('balance_amount', 14, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('supplier_id');
            $table->index('status');
            $table->index('pr_number');
        });

        // Create purchase_items table
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->integer('received_qty')->default(0);
            $table->integer('pending_qty')->default(0);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('gst_percent', 5, 2)->default(0);
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'received', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('purchase_id');
            $table->index('product_id');
        });

        // Create purchase_payments table
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->decimal('amount', 14, 2);
            $table->date('payment_date');
            $table->string('payment_mode', 50);
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            $table->index('purchase_id');
        });

        // Create purchase_deliveries table
        Schema::create('purchase_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->date('delivery_date');
            $table->string('received_by', 255)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            $table->index('purchase_id');
        });

        // Create purchase_delivery_items table
        Schema::create('purchase_delivery_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_delivery_id');
            $table->unsignedBigInteger('purchase_item_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity_received');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('purchase_delivery_id');
            $table->index('purchase_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_delivery_items');
        Schema::dropIfExists('purchase_deliveries');
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases_master');
    }
};
