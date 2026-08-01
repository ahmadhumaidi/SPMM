<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_networks', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('upline_referral_partner_id')->nullable()->constrained('referral_partners')->nullOnDelete();
            $table->foreignId('downline_referral_partner_id')->constrained('referral_partners')->cascadeOnDelete();
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('position', 32)->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['upline_referral_partner_id', 'downline_referral_partner_id'], 'affiliate_network_upline_downline_unique');
            $table->index(['downline_referral_partner_id', 'status']);
            $table->index(['upline_referral_partner_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_networks');
    }
};