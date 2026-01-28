<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {

            if (!Schema::hasColumn('order_payments', 'amount')) {
                $table->decimal('amount', 10, 2)->nullable()->after('payment_method');
            }

            if (!Schema::hasColumn('order_payments', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('amount');
            }
        });

        // 🔥 ENUM must be changed by raw SQL without DBAL
        DB::statement("ALTER TABLE `order_payments` MODIFY `payment_method` ENUM('cash','upi','credit_sale','other') NULL");
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
