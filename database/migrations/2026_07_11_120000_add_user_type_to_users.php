<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type', 20)->default('member')->after('email');
        });

        // Backfill from Spatie roles if they exist (skip on fresh install)
        if (Role::whereIn('name', ['member', 'escort'])->exists()) {
            User::role('member')->chunk(100, function ($users) {
                User::whereIn('id', $users->pluck('id'))->update(['user_type' => 'member']);
            });

            User::role('escort')->chunk(100, function ($users) {
                User::whereIn('id', $users->pluck('id'))->update(['user_type' => 'escort']);
            });

            // Remaining users (super_admin, admin, etc.)
            User::whereNull('user_type')->update(['user_type' => 'system_user']);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_type');
        });
    }
};
