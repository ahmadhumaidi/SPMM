<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subdomain')->nullable()->unique();
            $table->string('custom_domain')->nullable()->unique();
            $table->string('status')->default('active')->index();
            $table->text('address')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('province')->nullable()->index();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('study_programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('degree_level')->nullable();
            $table->string('accreditation')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique(['campus_id', 'code']);
            $table->index(['campus_id', 'status']);
        });

        Schema::create('class_tracks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique(['campus_id', 'name']);
            $table->index(['campus_id', 'status']);
        });

        Schema::create('fee_schemes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('class_track_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('registration_fee')->default(0);
            $table->unsignedBigInteger('building_fee')->default(0);
            $table->unsignedBigInteger('monthly_tuition_fee')->default(0);
            $table->unsignedBigInteger('total_initial_payment')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['campus_id', 'study_program_id', 'class_track_id', 'is_active'], 'fee_scheme_lookup_index');
        });

        Schema::create('user_campuses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'campus_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_campuses');
        Schema::dropIfExists('fee_schemes');
        Schema::dropIfExists('class_tracks');
        Schema::dropIfExists('study_programs');
        Schema::dropIfExists('campuses');
    }
};
