<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certificate_event_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('certificate_events')->cascadeOnDelete();

            $table->string('recipient_name');
            $table->string('certificate_title')->nullable();
            $table->string('award_title')->nullable();
            $table->string('activity_name')->nullable();
            $table->text('recognition_reason')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('signatory_name')->nullable();
            $table->date('issued_date')->nullable();
            $table->json('custom_fields')->nullable();

            $table->string('generated_file_path')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_event_recipients');
    }
};
