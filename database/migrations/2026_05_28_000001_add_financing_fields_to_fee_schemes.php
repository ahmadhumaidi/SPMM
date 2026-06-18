<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_schemes', function (Blueprint $table): void {
            $table->string('financing_model')->default('spb_spp')->after('class_track_id')->index();
            $table->unsignedBigInteger('ukt_total')->default(0)->after('monthly_tuition_fee');
            $table->json('development_installments_json')->nullable()->after('total_initial_payment');
            $table->json('tuition_installments_json')->nullable()->after('development_installments_json');
            $table->json('ukt_installments_json')->nullable()->after('tuition_installments_json');
        });
    }

    public function down(): void
    {
        Schema::table('fee_schemes', function (Blueprint $table): void {
            $table->dropColumn([
                'financing_model',
                'ukt_total',
                'development_installments_json',
                'tuition_installments_json',
                'ukt_installments_json',
            ]);
        });
    }
};
