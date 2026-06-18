<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_schemes', function (Blueprint $table): void {
            $table->boolean('uses_custom_installments')->default(false)->after('installment_schedule_json');
            $table->json('custom_installment_schedule_json')->nullable()->after('uses_custom_installments');
        });
    }

    public function down(): void
    {
        Schema::table('fee_schemes', function (Blueprint $table): void {
            $table->dropColumn([
                'uses_custom_installments',
                'custom_installment_schedule_json',
            ]);
        });
    }
};
