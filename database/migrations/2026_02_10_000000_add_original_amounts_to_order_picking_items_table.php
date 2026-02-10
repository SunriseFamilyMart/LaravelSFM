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
        Schema::table('order_picking_items', function (Blueprint $table) {
            $table->decimal('original_tax_amount', 8, 2)->nullable()->after('status');
            $table->decimal('original_discount', 8, 2)->nullable()->after('original_tax_amount');
            $table->decimal('original_price', 8, 2)->nullable()->after('original_discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_picking_items', function (Blueprint $table) {
            $table->dropColumn(['original_tax_amount', 'original_discount', 'original_price']);
        });
    }
};
