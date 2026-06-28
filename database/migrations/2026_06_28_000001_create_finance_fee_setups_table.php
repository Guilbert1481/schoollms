<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_fee_setups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->foreignId('education_node_id')->nullable()->constrained('education_nodes')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->unsignedTinyInteger('year_level')->nullable();
            $table->string('fee_type', 40)->default('tuition');
            $table->string('code', 40);
            $table->string('name');
            $table->string('billing_basis', 24)->default('fixed');
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'code'], 'finance_fee_setups_school_code_unique');
            $table->index(['school_id', 'academic_year_id', 'term_id'], 'finance_fee_setups_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_fee_setups');
    }
};
