<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_conversions', function (Blueprint $table): void {
            $table->string('herregistration_payout_proof_path')->nullable()->after('herregistration_paid_at');
            $table->text('herregistration_payout_notes')->nullable()->after('herregistration_payout_proof_path');
            $table->string('semester1_payout_proof_path')->nullable()->after('semester1_paid_at');
            $table->text('semester1_payout_notes')->nullable()->after('semester1_payout_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('referral_conversions', function (Blueprint $table): void {
            $table->dropColumn([
                'herregistration_payout_proof_path',
                'herregistration_payout_notes',
                'semester1_payout_proof_path',
                'semester1_payout_notes',
            ]);
        });
    }
};
