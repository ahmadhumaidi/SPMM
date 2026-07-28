<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_schemes', function (Blueprint $table): void {
            $table->json('custom_payment_items_json')->nullable()->after('ukt_installments_json');
        });
    }

    public function down(): void
    {
        Schema::table('fee_schemes', function (Blueprint $table): void {
            $table->dropColumn('custom_payment_items_json');
        });
    }
};