<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_trip_reassignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('delivery_trip_id')
                ->constrained('delivery_trips')
                ->onDelete('cascade');

            $table->foreignId('from_delivery_man_id')
                ->nullable()
                ->constrained('delivery_men')
                ->nullOnDelete();

            $table->foreignId('to_delivery_man_id')
                ->constrained('delivery_men')
                ->onDelete('cascade');

            $table->unsignedBigInteger('reassigned_by_admin_id')->nullable();

            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();

            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['delivery_trip_id']);
            $table->index(['to_delivery_man_id']);
            $table->index(['from_delivery_man_id']);
            $table->index(['reassigned_by_admin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_trip_reassignments');
    }
};
