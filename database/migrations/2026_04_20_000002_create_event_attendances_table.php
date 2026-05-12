<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('certificate_events')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('certificate_event_recipients')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('status', 20)->default('present');
            $table->timestamps();

            $table->unique(['event_id', 'recipient_id', 'attendance_date'], 'event_attendance_unique');
            $table->index(['event_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendances');
    }
};
