<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_conversions', function (Blueprint $table): void {
            $table->unsignedBigInteger('herregistration_commission_amount')->default(500000)->after('commission_status');
            $table->string('herregistration_commission_status')->default('pending')->after('herregistration_commission_amount')->index();
            $table->timestamp('herregistration_paid_at')->nullable()->after('herregistration_commission_status');
            $table->unsignedBigInteger('semester1_commission_amount')->default(500000)->after('herregistration_paid_at');
            $table->string('semester1_commission_status')->default('pending')->after('semester1_commission_amount')->index();
            $table->timestamp('semester1_paid_at')->nullable()->after('semester1_commission_status');
        });
    }

    public function down(): void
    {
        Schema::table('referral_conversions', function (Blueprint $table): void {
            $table->dropColumn([
                'herregistration_commission_amount',
                'herregistration_commission_status',
                'herregistration_paid_at',
                'semester1_commission_amount',
                'semester1_commission_status',
                'semester1_paid_at',
            ]);
        });
    }
};
