<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('prospect_status')->nullable()->after('lead_status')->index();
        });

        Schema::create('lead_prospect_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status')->index();
            $table->string('meta_event_name')->index();
            $table->json('meta_payload_json')->nullable();
            $table->string('meta_status')->default('pending')->index();
            $table->text('meta_error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_prospect_events');

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn('prospect_status');
        });
    }
};
