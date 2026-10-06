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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable();
            }

            if (!Schema::hasColumn('users', 'email')) {
                $table->string('email')->unique()->nullable();
            }

            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable();
            }

            if (!Schema::hasColumn('users', 'access_token')) {
                $table->text('access_token')->nullable();
            }

            if (!Schema::hasColumn('users', 'refresh_token')) {
                $table->text('refresh_token')->nullable();
            }

            if (!Schema::hasColumn('users', 'accounts_user_id')) {
                $table->string('accounts_user_id')->nullable();
            }

            if (!Schema::hasColumn('users', 'token_expires_at')) {
                $table->timestamp('token_expires_at')->nullable();
            }

            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }

            if (!Schema::hasColumn('users', 'setup_completed_at')) {
                $table->timestamp('setup_completed_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'name',
                'email',
                'avatar',
                'access_token',
                'refresh_token',
                'accounts_user_id',
                'token_expires_at',
                'email_verified_at',
                'setup_completed_at',
            ];

            $existingColumns = array_filter($columns, fn($column) => Schema::hasColumn('users', $column));

            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
