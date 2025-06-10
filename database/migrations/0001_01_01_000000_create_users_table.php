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
        // Create plans table first (users will reference it)
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2);
            $table->string('billing_period'); // monthly, quarterly, yearly
            $table->json('features')->nullable(); // Array of features
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes(); // Added for plans
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Profile information
            $table->string('gender')->nullable();
            $table->string('searching_for')->nullable();
            $table->date('birth_date')->nullable();
            $table->integer('age')->nullable();
            $table->text('bio')->nullable();
            $table->string('profile_picture')->nullable();
            $table->json('gallery')->nullable();

            // Location
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Contact
            $table->string('phone_number')->nullable();
            $table->boolean('phone_verified')->default(false);

            // Verification
            $table->boolean('is_escort')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->string('verification_documents')->nullable();

            // Stats
            $table->integer('view_count')->default(0);
            $table->float('rating', 2, 1)->nullable();
            $table->integer('review_count')->default(0);

            $table->string('status')->default('pending'); // pending, active, suspended, banned
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->string('payment_id')->nullable();
            $table->string('status')->default('pending'); // active, canceled, expired, incomplete, incomplete_expired, past_due, unpaid, trialing
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->json('payment_data')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Added for subscriptions
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Additional tables
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->integer('rating');
            $table->text('comment');
            $table->timestamps();
            $table->softDeletes(); // Added for reviews
        });

        Schema::create('user_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('document_type');
            $table->string('document_path');
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Added for verifications
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('value')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes(); // Added for plan features
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('user_verifications');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('plans');
    }
};
