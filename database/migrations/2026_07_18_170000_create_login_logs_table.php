<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roadmap Phase 4 — login success/failure log backing the superadmin
 * "Logins" page and the failed-auth threshold alert. Append-only like
 * audit_logs: INSERT-only, created_at only, no FK constraints.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->string('event', 16); // success | failed
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['email', 'event', 'created_at']);
            $table->index(['ip', 'event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
