<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_payments', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('status');
            }

            if (! Schema::hasColumn('student_payments', 'proof_path')) {
                $table->string('proof_path')->nullable()->after('payment_method');
            }

            if (! Schema::hasColumn('student_payments', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('proof_path');
            }

            if (! Schema::hasColumn('student_payments', 'verification_status')) {
                $table->string('verification_status')->default('unverified')->after('submitted_at');
            }

            if (! Schema::hasColumn('student_payments', 'verified_by_user_id')) {
                $table->foreignId('verified_by_user_id')->nullable()->after('verification_status')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('student_payments', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by_user_id');
            }

            if (! Schema::hasColumn('student_payments', 'verification_notes')) {
                $table->text('verification_notes')->nullable()->after('verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_payments', function (Blueprint $table): void {
            foreach ([
                'verification_notes',
                'verified_at',
                'verified_by_user_id',
                'verification_status',
                'submitted_at',
                'proof_path',
                'payment_method',
            ] as $column) {
                if (Schema::hasColumn('student_payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
