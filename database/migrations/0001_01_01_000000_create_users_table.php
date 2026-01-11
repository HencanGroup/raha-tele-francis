<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create counties table (Kenya counties)
        Schema::create('counties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create towns table (Kenya towns)
        Schema::create('towns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('county_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            $table->index('county_id');
        });

        // Base users table - minimal information for authentication
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // Common fields for all users
            $table->string('phone_number')->nullable();
            $table->boolean('phone_verified')->default(false);

            // Credits System
            $table->decimal('credits', 10, 2)->default(0);
            $table->decimal('total_credits_earned', 10, 2)->default(0);
            $table->decimal('total_credits_spent', 10, 2)->default(0);
            $table->timestamp('last_credit_purchase_at')->nullable();
            $table->timestamp('credits_expire_at')->nullable();

            // Account status
            $table->enum('status', ['active', 'inactive', 'suspended', 'banned'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('email_verified_at');
        });

        // Laravel default password reset tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Laravel sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // User profiles table (for members/clients)
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            // Profile information
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->enum('searching_for', ['male', 'female', 'both', 'other'])->nullable();
            $table->date('birth_date')->nullable();
            $table->integer('age')->nullable();
            $table->text('bio')->nullable();
            $table->string('profile_picture')->nullable();
            $table->json('gallery')->nullable();

            // Location
            $table->foreignId('county_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('town_id')->nullable()->constrained()->onDelete('set null');
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Preferences
            $table->json('preferences')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['county_id', 'town_id']);
            $table->index('gender');
            $table->index('age');
        });

        // Escorts table
        Schema::create('escorts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            // Basic information
            $table->string('stage_name')->nullable();
            $table->enum('gender', ['male', 'female', 'transgender', 'other'])->nullable();
            $table->date('birth_date')->nullable();
            $table->integer('age')->nullable();
            $table->text('bio')->nullable();
            $table->string('profile_picture')->nullable();

            // Location & availability
            $table->foreignId('county_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('town_id')->nullable()->constrained()->onDelete('set null');
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('available')->default(true);
            $table->string('working_hours')->nullable();

            // Physical attributes
            $table->decimal('height', 5, 2)->nullable(); // cm
            $table->decimal('weight', 5, 2)->nullable(); // kg
            $table->enum('body_type', ['slim', 'athletic', 'average', 'curvy', 'muscular', 'stocky'])->nullable();
            $table->enum('hair_color', ['black', 'brown', 'blonde', 'red', 'gray', 'other'])->nullable();
            $table->enum('eye_color', ['brown', 'blue', 'green', 'hazel', 'gray', 'other'])->nullable();

            // Services & rates
            $table->json('services')->nullable();
            $table->json('special_features')->nullable();
            $table->json('languages')->nullable();
            $table->decimal('rate_per_hour', 10, 2)->nullable();
            $table->decimal('rate_per_night', 10, 2)->nullable();
            $table->json('custom_rates')->nullable();

            // Verification & status
            $table->boolean('is_verified')->default(false);
            $table->json('verification_documents')->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');

            // Stats & ratings
            $table->integer('view_count')->default(0);
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('review_count')->default(0);
            $table->integer('total_bookings')->default(0);

            // Earnings
            $table->decimal('earnings', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);

            // Settings
            $table->boolean('featured')->default(false);
            $table->boolean('accepting_new_clients')->default(true);
            $table->boolean('incall_available')->default(false);
            $table->boolean('outcall_available')->default(true);
            $table->json('travel_options')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Optimized indexes
            $table->index(['county_id', 'town_id']);
            $table->index(['available', 'is_verified']);
            $table->index(['gender', 'available']);
            $table->index('rating');
            $table->index('featured');
            $table->index('rate_per_hour');
            $table->index('created_at');
        });

        // Escort resources (photos, videos)
        Schema::create('escort_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escort_id')->constrained('escorts')->onDelete('cascade');
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
            $table->index('sort_order');
        });

        // Mpesa payments table
        Schema::create('mpesa_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Transaction Identifiers
            $table->string('transaction_id')->unique()->nullable();
            $table->string('reference')->nullable();

            // Payment Details
            $table->decimal('amount', 10, 2);
            $table->decimal('credits_awarded', 10, 2);
            $table->string('phone_number');

            // Status and Metadata
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');

            $table->timestamps();
            $table->softDeletes();

            // Optimized indexes
            $table->index(['user_id', 'status']);
            $table->index('transaction_id');
            $table->index('phone_number');
            $table->index('created_at');
        });

        // Credit award history table
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Transaction Details
            $table->enum('type', ['welcome', 'purchase', 'bonus', 'usage', 'refund', 'withdrawal', 'commission']);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);

            // Reference to source
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            // Description
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Optimized indexes
            $table->index(['user_id', 'type', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('type');
            $table->index('created_at');
        });

        // Chat conversations - simple one-to-one conversations
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();

            // Two participants in the conversation
            $table->foreignId('user_one_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_two_id')->constrained('users')->onDelete('cascade');

            // Conversation status
            $table->enum('status', ['active', 'blocked'])->default('active');
            $table->timestamp('last_message_at')->nullable();

            // User-specific settings (like Facebook's archive/mute)
            $table->boolean('user_one_muted')->default(false);
            $table->boolean('user_two_muted')->default(false);
            $table->boolean('user_one_archived')->default(false);
            $table->boolean('user_two_archived')->default(false);
            $table->boolean('user_one_blocked')->default(false);
            $table->boolean('user_two_blocked')->default(false);

            // Track last read time for each user
            $table->timestamp('user_one_last_read_at')->nullable();
            $table->timestamp('user_two_last_read_at')->nullable();

            // For paid conversations (escort chats)
            $table->boolean('is_paid_conversation')->default(false);
            $table->decimal('total_credits_spent', 10, 2)->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->foreignId('credit_payer_id')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Ensure unique conversation between any two users
            $table->unique(['user_one_id', 'user_two_id']);

            // Optimized indexes
            $table->index(['user_one_id', 'status', 'last_message_at']);
            $table->index(['user_two_id', 'status', 'last_message_at']);
            $table->index(['user_one_id', 'user_one_archived']);
            $table->index(['user_two_id', 'user_two_archived']);
            $table->index('last_message_at');
            $table->index('is_paid_conversation');
        });

        // Chat messages
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('conversation_id')
                ->constrained('chat_conversations')
                ->cascadeOnDelete();

            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('receiver_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Message content
            $table->text('message')->nullable(); // Nullable for media-only messages

            $table->enum('type', [
                'text',
                'image',
                'video',
                'audio',
                'file',
                'sticker',
                'gif',
            ])->default('text');

            // Media attachments
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_size')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->json('attachment_meta')->nullable(); // Dimensions, duration, etc.

            // Message metadata
            $table->json('metadata')->nullable(); // Mentions, extra info
            $table->unsignedBigInteger('reply_to_id')->nullable();
            $table->foreign('reply_to_id')
                ->references('id')
                ->on('chat_messages')
                ->nullOnDelete();

            // Delivery status
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_delivered')->default(false);
            $table->timestamp('delivered_at')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();

            // Reactions
            $table->json('reactions')->nullable(); // {"user_id": "reaction"}

            // Paid messages (escort chats)
            $table->boolean('requires_credit')->default(false);
            $table->decimal('credit_cost', 10, 2)->nullable();

            $table->foreignId('credit_transaction_id')
                ->nullable()
                ->constrained('credit_transactions')
                ->nullOnDelete();

            $table->boolean('is_paid')->default(false);
            $table->boolean('payment_verified')->default(false);

            $table->timestamps();

            // Optimized indexes
            $table->index(['conversation_id', 'created_at']);
            $table->index(['conversation_id', 'is_read']);
            $table->index(['conversation_id', 'is_deleted']);
            $table->index(['sender_id', 'receiver_id']);
            $table->index(['receiver_id', 'is_read']);
            $table->index(['requires_credit', 'is_paid']);
            $table->index('created_at');
        });

        // Reviews table
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('escort_id')->constrained('escorts')->onDelete('cascade');

            $table->tinyInteger('rating')->unsigned(); // 1-5
            $table->text('comment');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_visible')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Prevent duplicate reviews with unique constraint
            $table->unique(['user_id', 'escort_id']);

            // Optimized indexes
            $table->index(['escort_id', 'is_visible']);
            $table->index(['escort_id', 'rating']);
            $table->index(['user_id', 'escort_id']);
            $table->index('is_verified');
            $table->index('created_at');
        });

        // Favorite escorts
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('escort_id')->constrained('escorts')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            // Ensure unique favorites and optimize queries
            $table->unique(['user_id', 'escort_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['escort_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order of creation to avoid foreign key constraints
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('mpesa_payments');
        Schema::dropIfExists('escort_resources');
        Schema::dropIfExists('escorts');
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('towns');
        Schema::dropIfExists('counties');
    }
};