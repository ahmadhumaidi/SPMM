<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('whatsapp_broadcast_recipients');
        Schema::dropIfExists('whatsapp_broadcasts');

        if (! Schema::hasColumn('leads', 'whatsapp_opted_in_at')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->timestamp('whatsapp_opted_in_at')->nullable()->index();
                $table->timestamp('whatsapp_opted_out_at')->nullable()->index();
            });
        }

        Schema::create('whatsapp_broadcasts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('template_name')->nullable();
            $table->string('template_language', 16)->default('id');
            $table->string('lead_status')->nullable();
            $table->text('message_body')->nullable();
            $table->unsignedSmallInteger('interval_seconds')->default(45);
            $table->unsignedInteger('max_recipients')->default(50);
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('read_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_broadcast_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('whatsapp_broadcast_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_number');
            $table->string('status')->default('queued')->index();
            $table->string('provider_reference')->nullable()->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->timestamps();
            $table->unique(['whatsapp_broadcast_id', 'lead_id'], 'wa_broadcast_lead_unique');
        });

        if (! Schema::hasColumn('whatsapp_messages', 'whatsapp_broadcast_recipient_id')) {
            Schema::table('whatsapp_messages', function (Blueprint $table): void {
                $table->foreignId('whatsapp_broadcast_recipient_id')->nullable()
                    ->after('invoice_id')->constrained('whatsapp_broadcast_recipients')->nullOnDelete();
                $table->index('provider_reference');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('whatsapp_messages', 'whatsapp_broadcast_recipient_id')) {
            Schema::table('whatsapp_messages', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('whatsapp_broadcast_recipient_id');
                $table->dropIndex(['provider_reference']);
            });
        }

        Schema::dropIfExists('whatsapp_broadcast_recipients');
        Schema::dropIfExists('whatsapp_broadcasts');
    }
};
