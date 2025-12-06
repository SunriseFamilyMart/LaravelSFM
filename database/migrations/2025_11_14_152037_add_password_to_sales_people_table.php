<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('sales_people', function (Blueprint $table) {
            $table->string('password')->after('phone_number');
        });
    }

    public function down()
    {
        Schema::table('sales_people', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }

};
