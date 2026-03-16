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
    Schema::create('invoices', function (Blueprint $table) {
        $table->id();

        $table->foreignId('school_id')->constrained()->cascadeOnDelete();
        $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

        $table->decimal('total_amount', 12, 2);
        $table->decimal('paid_amount', 12, 2)->default(0);
        $table->decimal('balance', 12, 2);

        $table->enum('status', ['paid', 'partial', 'unpaid'])->default('unpaid');

        $table->date('due_date')->nullable();

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
