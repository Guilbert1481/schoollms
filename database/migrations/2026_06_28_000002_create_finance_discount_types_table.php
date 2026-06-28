<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_discount_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->string('discount_kind', 24)->default('percentage');
            $table->decimal('value', 12, 2)->default(0);
            $table->string('applies_to', 40)->default('total');
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'code'], 'finance_discount_types_school_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_discount_types');
    }
};
