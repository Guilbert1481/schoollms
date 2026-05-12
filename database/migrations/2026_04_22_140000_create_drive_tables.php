<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drive_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('drive_files')->cascadeOnDelete();
            $table->enum('type', ['folder', 'file'])->default('file');
            $table->string('name');
            $table->string('mime', 150)->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('path')->nullable(); // relative path on 'public' disk
            $table->timestamps();

            $table->index(['owner_id', 'parent_id']);
            $table->index(['parent_id', 'type']);
        });

        Schema::create('drive_file_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drive_file_id')->constrained('drive_files')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('permission', ['view', 'edit'])->default('view');
            $table->timestamps();

            $table->unique(['drive_file_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_file_shares');
        Schema::dropIfExists('drive_files');
    }
};
