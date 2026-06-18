<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_partners', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type')->default('staff')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('referral_code')->unique();
            $table->unsignedBigInteger('commission_amount')->default(0);
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('referral_partner_id')->nullable()->after('assigned_to_user_id')->constrained('referral_partners')->nullOnDelete();
            $table->string('referral_code')->nullable()->after('referral_partner_id')->index();
        });

        Schema::create('referral_conversions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('referral_partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('active_at')->nullable();
            $table->unsignedBigInteger('commission_amount')->default(0);
            $table->string('commission_status')->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_conversions');

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('referral_partner_id');
            $table->dropColumn('referral_code');
        });

        Schema::dropIfExists('referral_partners');
    }
};
