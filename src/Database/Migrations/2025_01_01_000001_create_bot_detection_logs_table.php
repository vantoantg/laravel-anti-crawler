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
        Schema::create('bot_detection_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('user_agent');
            $table->string('request_url', 500);
            $table->string('request_method', 10);
            $table->string('detection_reason', 255);
            $table->unsignedTinyInteger('risk_score')->default(0)->index(); // 0-100
            $table->json('headers')->nullable();
            $table->enum('action_taken', ['logged', 'challenged', 'blocked'])->default('logged')->index();
            $table->timestamp('created_at')->index();

            // Composite indexes for common queries
            $table->index(['ip_address', 'created_at']);
            $table->index(['risk_score', 'created_at']);
            $table->index(['action_taken', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_detection_logs');
    }
};
