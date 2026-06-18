<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_biodatas', function (Blueprint $table): void {
            $table->id();
            $table->string('student_type')->index();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('selection_number')->nullable();
            $table->string('student_number')->nullable()->index();
            $table->string('group')->nullable();
            $table->string('cohort_year')->nullable()->index();
            $table->string('financial_status')->nullable()->index();
            $table->string('entry_type')->nullable();
            $table->string('class_time')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion')->nullable();
            $table->string('gender')->nullable();
            $table->text('address')->nullable();
            $table->text('mailing_address')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('village')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('rt_rw')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('identity_number')->nullable()->index();
            $table->string('mother_name')->nullable();
            $table->string('mother_identity_number')->nullable();
            $table->string('father_name')->nullable();
            $table->string('father_identity_number')->nullable();
            $table->string('family_number')->nullable();
            $table->string('family_name')->nullable();
            $table->string('family_relationship')->nullable();
            $table->string('company_name')->nullable();
            $table->string('job_title')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_city')->nullable();
            $table->string('company_postal_code')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_fax')->nullable();
            $table->unsignedBigInteger('development_contribution')->default(0);
            $table->unsignedBigInteger('semester_education_contribution')->default(0);
            $table->unsignedBigInteger('almamater_jacket_fee')->default(0);
            $table->unsignedBigInteger('form_fee')->default(0);
            $table->timestamps();

            $table->index(['student_type', 'campus_id', 'cohort_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_biodatas');
    }
};
