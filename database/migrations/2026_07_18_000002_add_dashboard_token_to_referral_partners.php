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
        Schema::table('referral_partners', function (Blueprint $table): void {
            $table->string('dashboard_token', 80)->nullable()->unique()->after('verification_token');
        });

        DB::table('referral_partners')
            ->where('type', 'umum')
            ->whereNull('dashboard_token')
            ->orderBy('id')
            ->select('id')
            ->chunk(100, function ($partners): void {
                foreach ($partners as $partner) {
                    DB::table('referral_partners')
                        ->where('id', $partner->id)
                        ->update(['dashboard_token' => Str::random(64)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('referral_partners', function (Blueprint $table): void {
            $table->dropUnique(['dashboard_token']);
            $table->dropColumn('dashboard_token');
        });
    }
};
