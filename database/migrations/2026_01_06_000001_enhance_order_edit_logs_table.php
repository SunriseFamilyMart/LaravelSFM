<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EnhanceOrderEditLogsTable extends Migration
{
    public function up()
    {
        Schema::table('order_edit_logs', function (Blueprint $table) {
            // Add columns only if they don't exist
            if (!Schema::hasColumn('order_edit_logs', 'action')) {
                $table->string('action', 50)->nullable()->after('delivery_man_id')
                    ->comment('quantity_increase, quantity_decrease, partial_return, full_return, price_adjustment');
            }
            
            if (!Schema::hasColumn('order_edit_logs', 'edited_by_type')) {
                $table->string('edited_by_type', 30)->nullable()->after('action')
                    ->comment('admin, delivery_man, sales_person, system');
            }
            
            if (!Schema::hasColumn('order_edit_logs', 'edited_by_id')) {
                $table->unsignedBigInteger('edited_by_id')->nullable()->after('edited_by_type');
            }
            
            if (!Schema::hasColumn('order_edit_logs', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->default(0)->after('new_price')
                    ->comment('Price per unit at the time of edit');
            }
            
            if (!Schema::hasColumn('order_edit_logs', 'discount_per_unit')) {
                $table->decimal('discount_per_unit', 12, 2)->default(0)->after('unit_price');
            }
            
            if (!Schema::hasColumn('order_edit_logs', 'tax_per_unit')) {
                $table->decimal('tax_per_unit', 12, 2)->default(0)->after('discount_per_unit');
            }
            
            if (!Schema::hasColumn('order_edit_logs', 'price_difference')) {
                $table->decimal('price_difference', 12, 2)->default(0)->after('tax_per_unit')
                    ->comment('Difference between old_price and new_price');
            }
            
            if (!Schema::hasColumn('order_edit_logs', 'quantity_difference')) {
                $table->integer('quantity_difference')->default(0)->after('price_difference')
                    ->comment('Difference between old_quantity and new_quantity');
            }
            
            if (!Schema::hasColumn('order_edit_logs', 'return_type')) {
                $table->string('return_type', 20)->nullable()->after('quantity_difference')
                    ->comment('partial, full, none');
            }
            
            if (!Schema::hasColumn('order_edit_logs', 'notes')) {
                $table->text('notes')->nullable()->after('return_type');
            }
            
            if (!Schema::hasColumn('order_edit_logs', 'order_amount_before')) {
                $table->decimal('order_amount_before', 12, 2)->nullable()->after('notes')
                    ->comment('Total order amount before this edit');
            }
            
            if (!Schema::hasColumn('order_edit_logs', 'order_amount_after')) {
                $table->decimal('order_amount_after', 12, 2)->nullable()->after('order_amount_before')
                    ->comment('Total order amount after this edit');
            }
        });
        
        // Add indexes for better performance
        Schema::table('order_edit_logs', function (Blueprint $table) {
            $indexes = collect(DB::select("SHOW INDEX FROM order_edit_logs"))->pluck('Key_name')->toArray();
            
            if (!in_array('order_edit_logs_action_index', $indexes)) {
                $table->index('action', 'order_edit_logs_action_index');
            }
            if (!in_array('order_edit_logs_return_type_index', $indexes)) {
                $table->index('return_type', 'order_edit_logs_return_type_index');
            }
            if (!in_array('order_edit_logs_edited_by_index', $indexes)) {
                $table->index(['edited_by_type', 'edited_by_id'], 'order_edit_logs_edited_by_index');
            }
        });
    }

    public function down()
    {
        Schema::table('order_edit_logs', function (Blueprint $table) {
            $columns = [
                'action', 'edited_by_type', 'edited_by_id', 'unit_price', 
                'discount_per_unit', 'tax_per_unit', 'price_difference', 
                'quantity_difference', 'return_type', 'notes',
                'order_amount_before', 'order_amount_after'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('order_edit_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
