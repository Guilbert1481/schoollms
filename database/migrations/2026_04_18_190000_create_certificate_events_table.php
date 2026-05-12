<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certificate_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('certificate_templates')->cascadeOnDelete();

            $table->string('event_name');
            $table->string('event_type', 50)->default('other');
            $table->string('certificate_title_default')->nullable();
            $table->date('date_issued_default')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_events');
    }
};
