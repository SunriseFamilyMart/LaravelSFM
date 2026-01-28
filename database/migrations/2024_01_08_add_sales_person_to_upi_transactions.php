<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add sales_person_id to upi_transactions table
 * Required for tracking Sales Person payments
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upi_transactions', function (Blueprint $table) {
            // Add sales_person_id if not exists
            if (!Schema::hasColumn('upi_transactions', 'sales_person_id')) {
                $table->unsignedBigInteger('sales_person_id')->nullable()->after('delivery_man_id');
                $table->foreign('sales_person_id')->references('id')->on('sales_people')->onDelete('set null');
                $table->index('sales_person_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('upi_transactions', function (Blueprint $table) {
            $table->dropForeign(['sales_person_id']);
            $table->dropColumn('sales_person_id');
        });
    }
};
