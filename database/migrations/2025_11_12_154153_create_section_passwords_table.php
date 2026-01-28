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
        Schema::create('section_passwords', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique(); // e.g., 'orders', 'products', 'sales-person'
            $table->string('password'); // hashed
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_passwords');
    }
};
