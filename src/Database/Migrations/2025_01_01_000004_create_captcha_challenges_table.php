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
        Schema::create('captcha_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('challenge_type', 50); // 'turnstile', 'recaptcha_v2', etc.
            $table->string('challenge_token', 255)->unique();
            $table->boolean('is_solved')->default(false)->index();
            $table->timestamp('solved_at')->nullable();
            $table->timestamp('created_at');

            $table->index(['ip_address', 'created_at']);
            $table->index(['is_solved', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('captcha_challenges');
    }
};
