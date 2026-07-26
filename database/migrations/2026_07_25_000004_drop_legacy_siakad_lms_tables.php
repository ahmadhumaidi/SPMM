<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('lms_submissions');
        Schema::dropIfExists('lms_assignments');
        Schema::dropIfExists('lms_materials');
        Schema::dropIfExists('lms_modules');
        Schema::dropIfExists('study_plan_items');
        Schema::dropIfExists('study_plans');
        Schema::dropIfExists('course_classes');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('academic_terms');
    }

    public function down(): void
    {
        // Irreversible: the legacy schema is superseded by the migrations that follow.
    }
};
