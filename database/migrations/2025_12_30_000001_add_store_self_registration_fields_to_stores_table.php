<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Self registration metadata
            if (!Schema::hasColumn('stores', 'registration_source')) {
                $table->string('registration_source')->nullable()->after('gst_number'); // sales_person | self
            }
            if (!Schema::hasColumn('stores', 'approval_status')) {
                $table->string('approval_status')->default('approved')->after('registration_source'); // pending | approved | rejected
            }
            if (!Schema::hasColumn('stores', 'can_login')) {
                $table->boolean('can_login')->default(true)->after('approval_status');
            }

            // Store auth
            if (!Schema::hasColumn('stores', 'password')) {
                $table->string('password')->nullable()->after('can_login');
            }
            if (!Schema::hasColumn('stores', 'auth_token')) {
                // index is fine here; if column exists already, we skip entirely
                $table->string('auth_token', 120)->nullable()->index()->after('password');
            }

            // Admin approval tracking
            if (!Schema::hasColumn('stores', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('auth_token');
            }
            if (!Schema::hasColumn('stores', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Drop columns only if they exist (avoids errors on partial states)
            foreach ([
                'approved_at',
                'approved_by',
                'auth_token',
                'password',
                'can_login',
                'approval_status',
                'registration_source',
            ] as $col) {
                if (Schema::hasColumn('stores', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
