<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();

            // The escort (user) requesting the payout.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Credits being withdrawn (reserved from escort.balance).
            $table->decimal('amount', 10, 2)->default(0);
            // KES payout amount = credits x credit_value_kes.
            $table->decimal('amount_kes', 10, 2)->default(0);

            // Recipient phone in Daraja format (2547...).
            $table->string('phone_number', 20);

            // Lifecycle: pending -> processing -> completed | failed | cancelled.
            $table->string('status', 20)->default('pending');

            // B2C correlation + settlement fields.
            $table->string('mpesa_reference', 255)->nullable()->index();
            $table->string('transaction_id', 255)->nullable();
            $table->text('failure_reason')->nullable();

            // Audit — who approved the payout and when it finalised.
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
