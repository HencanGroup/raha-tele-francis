<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Only migrate data if the member role exists (seeded) — on fresh
        // installs the users table is empty so there is nothing to migrate.
        if (Role::where('name', 'member')->exists()) {
            User::role('member')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::table('members')->insert([
                        'user_id' => $user->id,
                        'credits' => $user->credits ?? 0,
                        'total_credits_earned' => $user->total_credits_earned ?? 0,
                        'total_credits_spent' => $user->total_credits_spent ?? 0,
                        'last_credit_purchase_at' => $user->last_credit_purchase_at,
                        'credits_expire_at' => $user->credits_expire_at,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
        }

        // Drop credit columns from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'credits',
                'total_credits_earned',
                'total_credits_spent',
                'last_credit_purchase_at',
                'credits_expire_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('credits', 10, 2)->default(0);
            $table->decimal('total_credits_earned', 10, 2)->default(0);
            $table->decimal('total_credits_spent', 10, 2)->default(0);
            $table->timestamp('last_credit_purchase_at')->nullable();
            $table->timestamp('credits_expire_at')->nullable();
        });

        // Restore credit data from members back to users
        DB::table('members')->chunk(100, function ($members) {
            foreach ($members as $member) {
                DB::table('users')
                    ->where('id', $member->user_id)
                    ->update([
                        'credits' => $member->credits,
                        'total_credits_earned' => $member->total_credits_earned,
                        'total_credits_spent' => $member->total_credits_spent,
                        'last_credit_purchase_at' => $member->last_credit_purchase_at,
                        'credits_expire_at' => $member->credits_expire_at,
                    ]);
            }
        });

        Schema::dropIfExists('members');
    }
};
