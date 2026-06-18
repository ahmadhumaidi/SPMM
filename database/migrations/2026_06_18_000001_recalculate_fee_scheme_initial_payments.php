<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('fee_schemes')
            ->select(['id', 'installment_schedule_json'])
            ->orderBy('id')
            ->chunkById(100, function ($feeSchemes): void {
                foreach ($feeSchemes as $feeScheme) {
                    $schedule = json_decode($feeScheme->installment_schedule_json ?: '[]', true);

                    if (! is_array($schedule) || $schedule === []) {
                        DB::table('fee_schemes')
                            ->where('id', $feeScheme->id)
                            ->update(['total_initial_payment' => 0]);

                        continue;
                    }

                    usort($schedule, fn (array $a, array $b): int => ((int) ($a['month'] ?? 0)) <=> ((int) ($b['month'] ?? 0)));

                    $firstMonth = $schedule[0] ?? [];
                    $totalInitialPayment = (int) ($firstMonth['development_fee'] ?? 0)
                        + (int) ($firstMonth['tuition_fee'] ?? 0)
                        + (int) ($firstMonth['ukt'] ?? 0);

                    DB::table('fee_schemes')
                        ->where('id', $feeScheme->id)
                        ->update(['total_initial_payment' => $totalInitialPayment]);
                }
            });
    }

    public function down(): void
    {
        // Data-only recalculation; no rollback needed.
    }
};
