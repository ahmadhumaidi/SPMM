<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\StudentPayment;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class NotifyUnarchivedReceipts extends Command
{
    protected $signature = 'spmm:receipts:notify-unarchived';

    protected $description = 'Notify super admins when there are paid receipts that have not been archived yet.';

    public function handle(): int
    {
        $count = StudentPayment::query()
            ->whereIn('status', ['paid', 'waived'])
            ->whereNull('receipt_pdf_path')
            ->count();

        if ($count === 0) {
            $this->info('No unarchived receipts found.');

            return self::SUCCESS;
        }

        $recipients = User::query()->where('role', UserRole::SuperAdmin->value)->get();

        Notification::make()
            ->title('Ada '.$count.' kwitansi belum diarsipkan')
            ->body('Buka menu Student Payments, buka/unduh kwitansi yang belum tersimpan supaya bisa dicadangkan ke server dan diarsipkan manual.')
            ->warning()
            ->sendToDatabase($recipients);

        $this->info("Notified {$recipients->count()} super admin(s) about {$count} unarchived receipt(s).");

        return self::SUCCESS;
    }
}
