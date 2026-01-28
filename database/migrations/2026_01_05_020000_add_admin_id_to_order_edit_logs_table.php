<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_edit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('order_edit_logs', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('delivery_man_id');
                $table->index(['admin_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_edit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('order_edit_logs', 'admin_id')) {
                $table->dropIndex(['admin_id']);
                $table->dropColumn('admin_id');
            }
        });
    }
};
