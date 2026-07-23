<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_partners', function (Blueprint $table): void {
            $table->string('npwp', 32)->nullable()->after('source_information');
            $table->string('terms_document_path')->nullable()->after('identity_card_path');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_document_path');
            $table->timestamp('tax_notice_accepted_at')->nullable()->after('terms_accepted_at');
            $table->string('digital_signature_path')->nullable()->after('tax_notice_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('referral_partners', function (Blueprint $table): void {
            $table->dropColumn([
                'npwp',
                'terms_document_path',
                'terms_accepted_at',
                'tax_notice_accepted_at',
                'digital_signature_path',
            ]);
        });
    }
};
