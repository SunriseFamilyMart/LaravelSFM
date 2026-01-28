<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add sales_person_id to upi_transactions table
 * Required for Sales Person UPI payment flow
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upi_transactions', function (Blueprint $table) {
            // Check if column exists before adding
            if (!Schema::hasColumn('upi_transactions', 'sales_person_id')) {
                $table->unsignedBigInteger('sales_person_id')
                    ->nullable()
                    ->after('delivery_man_id')
                    ->comment('Sales person who initiated the payment');
                
                // Add foreign key if sales_people table exists
                if (Schema::hasTable('sales_people')) {
                    $table->foreign('sales_person_id')
                        ->references('id')
                        ->on('sales_people')
                        ->onDelete('set null');
                }
                
                // Add index for faster queries
                $table->index('sales_person_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('upi_transactions', function (Blueprint $table) {
            // Drop foreign key first
            if (Schema::hasColumn('upi_transactions', 'sales_person_id')) {
                try {
                    $table->dropForeign(['sales_person_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                $table->dropIndex(['sales_person_id']);
                $table->dropColumn('sales_person_id');
            }
        });
    }
};
