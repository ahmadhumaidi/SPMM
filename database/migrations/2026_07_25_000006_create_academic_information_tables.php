<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_terms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('academic_year')->index();
            $table->string('semester_type')->default('ganjil')->index();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->index();
            $table->string('name');
            $table->unsignedTinyInteger('credits')->default(3);
            $table->unsignedTinyInteger('semester_level')->nullable()->index();
            $table->string('course_type')->default('wajib')->index();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique(['campus_id', 'code']);
        });

        Schema::create('course_classes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_track_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lecturer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('class_name')->default('A');
            $table->string('day_of_week')->nullable();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('room')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('status')->default('open')->index();
            $table->timestamps();
        });

        Schema::create('study_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_term_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['lead_id', 'academic_term_id']);
        });

        Schema::create('study_plan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('study_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_class_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('taken')->index();
            $table->decimal('assignment_score', 5, 2)->nullable();
            $table->decimal('midterm_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->decimal('final_numeric_score', 5, 2)->nullable();
            $table->string('final_grade', 4)->nullable();
            $table->decimal('grade_point', 3, 2)->nullable();
            $table->timestamps();

            $table->unique(['study_plan_id', 'course_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_plan_items');
        Schema::dropIfExists('study_plans');
        Schema::dropIfExists('course_classes');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('academic_terms');
    }
};
