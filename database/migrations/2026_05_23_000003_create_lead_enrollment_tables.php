<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->restrictOnDelete();
            $table->foreignId('study_program_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_track_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_channel')->default('public_form')->index();
            $table->string('source_detail')->nullable();
            $table->string('full_name');
            $table->string('whatsapp_number')->index();
            $table->string('origin_school')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->string('lead_status')->default('in_pool')->index();
            $table->string('payment_status')->default('unpaid')->index();
            $table->string('enrollment_status')->default('calon_mahasiswa')->index();
            $table->string('pemberkasan_token')->nullable()->unique();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['campus_id', 'assigned_to_user_id', 'lead_status']);
            $table->index(['payment_status', 'enrollment_status']);
        });

        Schema::create('student_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nik', 32);
            $table->string('nisn', 32)->nullable();
            $table->string('birth_place');
            $table->date('birth_date');
            $table->string('gender', 16);
            $table->string('religion')->nullable();
            $table->string('mother_name');
            $table->string('father_name')->nullable();
            $table->text('address');
            $table->string('province');
            $table->string('city');
            $table->string('district');
            $table->string('village');
            $table->string('postal_code', 16)->nullable();
            $table->string('school_npsn', 32)->nullable();
            $table->string('citizenship')->default('Indonesia');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->json('pddikti_payload_json')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('student_numbers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nim')->unique();
            $table->foreignId('issued_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('issued_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_numbers');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('leads');
    }
};
