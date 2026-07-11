<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->decimal('credits', 10, 2)->default(0);
            $table->decimal('total_credits_earned', 10, 2)->default(0);
            $table->decimal('total_credits_spent', 10, 2)->default(0);
            $table->timestamp('last_credit_purchase_at')->nullable();
            $table->timestamp('credits_expire_at')->nullable();

            $table->string('social_id')->nullable();
            $table->string('social_provider')->nullable();
            $table->string('social_avatar')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['social_provider', 'social_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
