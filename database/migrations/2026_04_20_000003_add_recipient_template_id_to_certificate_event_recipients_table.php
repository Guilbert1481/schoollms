<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('certificate_event_recipients', function (Blueprint $table) {
            $table->foreignId('recipient_template_id')
                ->nullable()
                ->after('event_id')
                ->constrained('certificate_templates')
                ->nullOnDelete();

            $table->index(['event_id', 'recipient_template_id'], 'cer_event_template_idx');
        });
    }

    public function down(): void
    {
        Schema::table('certificate_event_recipients', function (Blueprint $table) {
            $table->dropIndex('cer_event_template_idx');
            $table->dropConstrainedForeignId('recipient_template_id');
        });
    }
};
