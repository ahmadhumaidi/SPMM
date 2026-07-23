<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_payments', function (Blueprint $table): void {
            $table->foreignId('payment_item_id')->nullable()->after('invoice_id')->constrained('payment_items')->nullOnDelete();
            $table->string('payment_type')->default('scheduled')->after('payment_item_id');
            $table->string('payment_method')->nullable()->after('status');
            $table->string('proof_path')->nullable()->after('payment_method');
            $table->timestamp('submitted_at')->nullable()->after('proof_path');
            $table->string('verification_status')->default('unverified')->after('submitted_at');
            $table->foreignId('verified_by_user_id')->nullable()->after('verification_status')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by_user_id');
            $table->text('verification_notes')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('student_payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('payment_item_id');
            $table->dropConstrainedForeignId('verified_by_user_id');
            $table->dropColumn(['payment_type', 'payment_method', 'proof_path', 'submitted_at', 'verification_status', 'verified_at', 'verification_notes']);
        });
    }
};
