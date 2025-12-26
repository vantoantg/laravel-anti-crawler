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
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->text('reason');
            $table->enum('blocked_by', ['auto', 'manual'])->default('auto');
            $table->unsignedBigInteger('blocked_by_user_id')->nullable();
            $table->timestamp('expires_at')->nullable()->index(); // NULL = permanent
            $table->timestamps();

            $table->index(['ip_address', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_ips');
    }
};
