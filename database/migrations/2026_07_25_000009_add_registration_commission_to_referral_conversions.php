<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_conversions', function (Blueprint $table): void {
            $table->unsignedBigInteger('registration_commission_amount')->default(0)->after('commission_status');
            $table->string('registration_commission_status')->default('pending')->after('registration_commission_amount');
            $table->timestamp('registration_paid_at')->nullable()->after('registration_commission_status');
            $table->string('registration_payout_proof_path')->nullable()->after('registration_paid_at');
            $table->text('registration_payout_notes')->nullable()->after('registration_payout_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('referral_conversions', function (Blueprint $table): void {
            $table->dropColumn([
                'registration_commission_amount',
                'registration_commission_status',
                'registration_paid_at',
                'registration_payout_proof_path',
                'registration_payout_notes',
            ]);
        });
    }
};
