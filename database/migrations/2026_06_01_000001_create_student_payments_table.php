<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_scheme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('month');
            $table->string('payment_label');
            $table->unsignedBigInteger('registration_fee')->default(0);
            $table->unsignedBigInteger('development_fee')->default(0);
            $table->unsignedBigInteger('tuition_fee')->default(0);
            $table->unsignedBigInteger('ukt')->default(0);
            $table->unsignedBigInteger('amount')->default(0);
            $table->date('due_date')->nullable();
            $table->string('status')->default('unpaid')->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('source_row_json')->nullable();
            $table->timestamps();

            $table->unique(['lead_id', 'month']);
            $table->index(['lead_id', 'status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_payments');
    }
};
