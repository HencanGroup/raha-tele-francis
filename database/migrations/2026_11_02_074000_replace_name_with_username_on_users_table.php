<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the `name` column on the `users` table with a unique `username`
     * column. Existing users are backfilled so `username = name` before the
     * `name` column is dropped.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->unique()->after('name');
        });

        // Backfill existing users so username = name before dropping name.
        DB::statement('UPDATE users SET username = name WHERE username IS NULL');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('name');
            $table->string('username')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migration by restoring the `name` column.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name')->nullable()->after('username');
        });

        DB::statement('UPDATE users SET name = username WHERE name IS NULL');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('username');
            $table->string('name')->nullable(false)->change();
        });
    }
};
