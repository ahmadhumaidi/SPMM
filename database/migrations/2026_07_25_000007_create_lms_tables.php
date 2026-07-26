<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_class_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->string('status')->default('published')->index();
            $table->timestamps();
        });

        Schema::create('lms_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lms_module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('material_type')->default('file')->index();
            $table->text('content')->nullable();
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->string('status')->default('published')->index();
            $table->timestamps();
        });

        Schema::create('lms_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lms_module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->unsignedInteger('max_score')->default(100);
            $table->string('status')->default('published')->index();
            $table->timestamps();
        });

        Schema::create('lms_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lms_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->string('status')->default('submitted')->index();
            $table->timestamps();

            $table->unique(['lms_assignment_id', 'lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_submissions');
        Schema::dropIfExists('lms_assignments');
        Schema::dropIfExists('lms_materials');
        Schema::dropIfExists('lms_modules');
    }
};
