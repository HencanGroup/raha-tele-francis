<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds explicit platform-commission ledger support to credit_transactions.
 *
 * Every member spend now writes a third immutable ledger row
 * (type 'platform_commission') recording the platform's cut of the 30/70
 * split. Previously the platform share existed only implicitly as the
 * difference between 'usage' and 'commission' rows, so admin widgets could
 * not report platform earnings from the source of truth.
 *
 * - type ENUM gains 'platform_commission'.
 * - user_id becomes nullable: a platform commission belongs to no user, so
 *   those rows carry user_id = NULL and balance_before/balance_after = 0.00
 *   (the platform has no wallet to move — the row is an income record).
 */
return new class extends Migration
{
    /**
     * Extend the ledger schema: nullable user_id + new platform_commission type.
     */
    public function up(): void
    {
        // Drop the FK first so the column can be rebuilt as nullable.
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->enum('type', [
                'welcome',
                'purchase',
                'bonus',
                'usage',
                'refund',
                'withdrawal',
                'commission',
                'platform_commission',
            ])->change();

            $table->unsignedBigInteger('user_id')->nullable()->change();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Revert: remove platform rows, shrink the ENUM, restore NOT NULL user_id.
     */
    public function down(): void
    {
        // Platform rows must be removed before restoring NOT NULL user_id.
        DB::table('credit_transactions')
            ->where('type', 'platform_commission')
            ->delete();

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->enum('type', [
                'welcome',
                'purchase',
                'bonus',
                'usage',
                'refund',
                'withdrawal',
                'commission',
            ])->change();

            $table->unsignedBigInteger('user_id')->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
