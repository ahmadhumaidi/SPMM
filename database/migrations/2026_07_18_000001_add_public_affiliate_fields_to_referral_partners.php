<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_partners', function (Blueprint $table): void {
            $table->string('email')->nullable()->after('student_account_id')->index();
            $table->string('phone')->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->string('source_information')->nullable()->after('address');
            $table->string('bank_name')->nullable()->after('source_information');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_account_name')->nullable()->after('bank_account_number');
            $table->string('identity_card_path')->nullable()->after('bank_account_name');
            $table->string('verification_token')->nullable()->unique()->after('identity_card_path');
            $table->timestamp('email_verified_at')->nullable()->after('verification_token');
        });
    }

    public function down(): void
    {
        Schema::table('referral_partners', function (Blueprint $table): void {
            $table->dropColumn([
                'email',
                'phone',
                'address',
                'source_information',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'identity_card_path',
                'verification_token',
                'email_verified_at',
            ]);
        });
    }
};
