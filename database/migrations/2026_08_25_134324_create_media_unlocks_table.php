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
        Schema::create('media_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('escort_resource_id')->constrained('escort_resources')->cascadeOnDelete();
            $table->decimal('credits_spent', 10, 2);
            $table->timestamps();

            // A member can only unlock a given media item once.
            $table->unique(['user_id', 'escort_resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_unlocks');
    }
};
