<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->string('otp');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['phone_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_otps');
    }
};
