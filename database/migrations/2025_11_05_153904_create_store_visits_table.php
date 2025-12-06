<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('sales_person_id');
            $table->string('photo'); // store path of uploaded photo
            $table->decimal('lat', 10, 7);
            $table->decimal('long', 10, 7);
            $table->string('address');
            $table->timestamps();

            // Optional foreign keys
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->foreign('sales_person_id')->references('id')->on('sales_people')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_visits');
    }
};