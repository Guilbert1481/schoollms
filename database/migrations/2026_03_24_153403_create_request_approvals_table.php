<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_approvals', function (Blueprint $table) {
            $table->id();

            // Request reference
            $table->foreignId('request_id')->constrained()->cascadeOnDelete();

            // Approver
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();

            // Role that can approve (admin, dean, etc.)
            $table->string('role')->nullable();

            // Approval order
            $table->integer('level')->default(1);

            // Approval status
            $table->enum('status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            // Remarks
            $table->text('remarks')->nullable();

            // Approval timestamp
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_approvals');
    }
};