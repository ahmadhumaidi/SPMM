<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->columns() as [$table, $column]) {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE jsonb USING {$column}::jsonb");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->columns() as [$table, $column]) {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE json USING {$column}::json");
        }
    }

    private function columns(): array
    {
        return [
            ['student_profiles', 'pddikti_payload_json'],
            ['payment_events', 'payload_json'],
            ['fee_schemes', 'development_installments_json'],
            ['fee_schemes', 'tuition_installments_json'],
            ['fee_schemes', 'ukt_installments_json'],
            ['fee_schemes', 'installment_schedule_json'],
            ['fee_schemes', 'custom_installment_schedule_json'],
            ['campuses', 'website_settings'],
            ['audit_logs', 'metadata'],
            ['student_payments', 'source_row_json'],
        ];
    }
};
