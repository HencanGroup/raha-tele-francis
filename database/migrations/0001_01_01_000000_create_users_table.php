<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * Counties
         */
        Schema::create('counties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        /**
         * Towns
         */
        Schema::create('towns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('county_id');
        });

        /**
         * Users
         */
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            $table->string('phone_number')->nullable();
            $table->boolean('phone_verified')->default(false);

            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('profile_picture')->nullable();

            $table->foreignId('county_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('town_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->json('meta_data')->nullable();

            $table->decimal('credits', 10, 2)->default(0);
            $table->decimal('total_credits_earned', 10, 2)->default(0);
            $table->decimal('total_credits_spent', 10, 2)->default(0);
            $table->timestamp('last_credit_purchase_at')->nullable();
            $table->timestamp('credits_expire_at')->nullable();

            $table->enum('status', ['active', 'inactive', 'suspended', 'banned'])->default('active');
            $table->timestamp('last_seen')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('gender');
            $table->index(['county_id', 'town_id']);
        });

        /**
         * Password reset tokens
         */
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        /**
         * Sessions
         */
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        /**
         * Escorts
         */
        Schema::create('escorts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('stage_name')->nullable();
            $table->text('bio')->nullable();

            $table->boolean('available')->default(true);
            $table->string('working_hours')->nullable();

            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->enum('body_type', ['slim', 'athletic', 'average', 'curvy', 'muscular', 'stocky'])->nullable();
            $table->enum('hair_color', ['black', 'brown', 'blonde', 'red', 'gray', 'other'])->nullable();
            $table->enum('eye_color', ['brown', 'blue', 'green', 'hazel', 'gray', 'other'])->nullable();

            $table->json('services')->nullable();
            $table->json('special_features')->nullable();
            $table->json('languages')->nullable();
            $table->decimal('rate_per_hour', 10, 2)->nullable();
            $table->decimal('rate_per_night', 10, 2)->nullable();
            $table->json('custom_rates')->nullable();

            $table->boolean('is_verified')->default(false);
            $table->json('verification_documents')->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');

            $table->integer('view_count')->default(0);
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('review_count')->default(0);
            $table->integer('total_bookings')->default(0);

            $table->decimal('earnings', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);

            $table->boolean('featured')->default(false);
            $table->boolean('accepting_new_clients')->default(true);
            $table->boolean('incall_available')->default(false);
            $table->boolean('outcall_available')->default(true);
            $table->json('travel_options')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['available', 'is_verified']);
            $table->index('rating');
            $table->index('featured');
            $table->index('rate_per_hour');
        });

        /**
         * Escort Resources
         */
        Schema::create('escort_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escort_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['photo', 'video']);
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('caption')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_public')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['escort_id', 'type']);
            $table->index(['escort_id', 'is_primary']);
        });

        /**
         * Mpesa Payments
         */
        Schema::create('mpesa_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('transaction_id')->unique()->nullable();
            $table->string('reference')->nullable();

            $table->decimal('amount', 10, 2);
            $table->decimal('credits_awarded', 10, 2);
            $table->string('phone_number');

            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('phone_number');
        });

        /**
         * Credit Transactions
         */
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['welcome', 'purchase', 'bonus', 'usage', 'refund', 'withdrawal', 'commission']);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);

            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
        });

        /**
         * Chat Conversations
         */
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_two_id')->constrained('users')->cascadeOnDelete();

            $table->enum('status', ['active', 'blocked'])->default('active');
            $table->timestamp('last_message_at')->nullable();

            $table->boolean('user_one_muted')->default(false);
            $table->boolean('user_two_muted')->default(false);
            $table->boolean('user_one_archived')->default(false);
            $table->boolean('user_two_archived')->default(false);
            $table->boolean('user_one_blocked')->default(false);
            $table->boolean('user_two_blocked')->default(false);

            $table->timestamp('user_one_last_read_at')->nullable();
            $table->timestamp('user_two_last_read_at')->nullable();

            $table->boolean('is_paid_conversation')->default(false);
            $table->decimal('total_credits_spent', 10, 2)->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->foreignId('credit_payer_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['user_one_id', 'user_two_id']);
        });

        /**
         * Chat Messages
         */
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();

            $table->uuid('client_id')->nullable()->index();
            $table->text('message')->nullable();

            $table->enum('type', ['text', 'image', 'video', 'audio', 'file', 'sticker', 'gif'])->default('text');

            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_size')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->json('attachment_meta')->nullable();

            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('reply_to_id')->nullable();
            $table->foreign('reply_to_id')->references('id')->on('messages')->nullOnDelete();

            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_delivered')->default(false);
            $table->timestamp('delivered_at')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->boolean('user_one_deleted')->default(false);
            $table->boolean('user_two_deleted')->default(false);

            $table->json('reactions')->nullable();

            $table->boolean('requires_credit')->default(false);
            $table->decimal('credit_cost', 10, 2)->nullable();

            $table->foreignId('credit_transaction_id')->nullable()->constrained()->nullOnDelete();

            $table->boolean('is_paid')->default(false);
            $table->boolean('payment_verified')->default(false);

            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['conversation_id', 'is_sent']);
            $table->index(['conversation_id', 'is_delivered']);
            $table->index(['conversation_id', 'is_read']);
            $table->index(['sender_id', 'receiver_id']);
            $table->index(['receiver_id', 'is_read']);
        });

        /**
         * Reviews
         */
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('escort_id')->constrained()->cascadeOnDelete();

            $table->tinyInteger('rating')->unsigned();
            $table->text('comment');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_visible')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'escort_id']);
            $table->index(['escort_id', 'is_visible']);
            $table->index(['escort_id', 'rating']);
        });

        /**
         * Favorites
         */
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('escort_id')->constrained()->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'escort_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('mpesa_payments');
        Schema::dropIfExists('escort_resources');
        Schema::dropIfExists('escorts');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('towns');
        Schema::dropIfExists('counties');
    }
};
