<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('referral_partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referral_conversion_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_payment_id')->nullable()->constrained('student_payments')->nullOnDelete();
            $table->string('stage', 40)->index();
            $table->string('commission_level', 40)->index();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('status', 30)->default('APPROVED')->index();
            $table->string('source', 40)->default('PAYMENT');
            $table->string('reference')->nullable()->unique();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['lead_id', 'stage', 'commission_level'], 'affiliate_commissions_lead_stage_level_unique');
            $table->index(['referral_partner_id', 'status'], 'affiliate_commissions_partner_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
    }
};
