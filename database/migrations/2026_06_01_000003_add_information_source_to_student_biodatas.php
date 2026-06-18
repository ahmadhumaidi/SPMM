<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_biodatas', function (Blueprint $table): void {
            $table->string('information_source')->nullable()->after('class_track_id')->index();
            $table->string('affiliator_code')->nullable()->after('information_source')->index();
            $table->string('information_source_other')->nullable()->after('affiliator_code');
        });
    }

    public function down(): void
    {
        Schema::table('student_biodatas', function (Blueprint $table): void {
            $table->dropColumn(['information_source', 'affiliator_code', 'information_source_other']);
        });
    }
};
