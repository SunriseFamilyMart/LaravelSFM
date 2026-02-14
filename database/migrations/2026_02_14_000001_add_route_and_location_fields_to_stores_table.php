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
        Schema::table('stores', function (Blueprint $table) {
            if (!Schema::hasColumn('stores', 'route_name')) {
                $table->string('route_name')->nullable()->after('store_name');
            }
            if (!Schema::hasColumn('stores', 'branch')) {
                $table->string('branch')->nullable()->after('route_name');
            }
            if (!Schema::hasColumn('stores', 'street_address')) {
                $table->text('street_address')->nullable()->after('address');
            }
            if (!Schema::hasColumn('stores', 'area')) {
                $table->string('area')->nullable()->after('street_address');
            }
            if (!Schema::hasColumn('stores', 'city')) {
                $table->string('city')->nullable()->after('area');
            }
            if (!Schema::hasColumn('stores', 'taluk')) {
                $table->string('taluk')->nullable()->after('city');
            }
            if (!Schema::hasColumn('stores', 'district')) {
                $table->string('district')->nullable()->after('taluk');
            }
            if (!Schema::hasColumn('stores', 'state')) {
                $table->string('state')->nullable()->after('district');
            }
            if (!Schema::hasColumn('stores', 'pincode')) {
                $table->string('pincode', 10)->nullable()->after('state');
            }
            if (!Schema::hasColumn('stores', 'full_address')) {
                $table->text('full_address')->nullable()->after('pincode');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $columns = [
                'full_address',
                'pincode',
                'state',
                'district',
                'taluk',
                'city',
                'area',
                'street_address',
                'branch',
                'route_name',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('stores', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
