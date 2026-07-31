<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('payment_proof_token', 64)->nullable()->after('pemberkasan_token');
        });

        foreach (DB::table('leads')->whereNull('payment_proof_token')->orderBy('id')->get(['id']) as $row) {
            DB::table('leads')->where('id', $row->id)->update(['payment_proof_token' => Str::random(64)]);
        }

        Schema::table('leads', function (Blueprint $table): void {
            $table->unique('payment_proof_token');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropUnique(['payment_proof_token']);
            $table->dropColumn('payment_proof_token');
        });
    }
};
