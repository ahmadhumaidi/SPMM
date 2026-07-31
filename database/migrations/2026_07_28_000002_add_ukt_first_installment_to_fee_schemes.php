<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_schemes', function (Blueprint $table): void {
            $table->unsignedBigInteger('ukt_first_installment_amount')->default(0)->after('ukt_total');
        });
    }

    public function down(): void
    {
        Schema::table('fee_schemes', function (Blueprint $table): void {
            $table->dropColumn('ukt_first_installment_amount');
        });
    }
};