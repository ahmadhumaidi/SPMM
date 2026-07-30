<?php

namespace App\Support;

use App\Enums\PaymentStatus;
use App\Models\Lead;
use App\Models\StudentPayment;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class FinancialStatusLabels
{
    public static function paymentStage(StudentPayment $payment): string
    {
        return (int) $payment->month === 0 ? 'Registrasi' : 'Herregistrasi';
    }

    public static function paymentStatus(StudentPayment $payment): string
    {
        $stage = self::paymentStage($payment);

        return match ($payment->status) {
            'paid', 'waived' => $stage,
            'pending' => 'Menunggu '.$stage,
            'unpaid' => 'Belum '.$stage,
            default => str((string) $payment->status)->replace('_', ' ')->title()->append(' '.$stage)->toString(),
        };
    }
    public static function leadStatus(?Lead $lead): string
    {
        if (! $lead) {
            return '-';
        }

        $payments = $lead->relationLoaded('studentPayments')
            ? $lead->studentPayments
            : $lead->studentPayments()->get(['id', 'lead_id', 'month', 'status', 'payment_type']);

        $billablePayments = $payments->filter(fn (StudentPayment $payment): bool => $payment->payment_type !== 'manual');

        if (self::hasPaidHerregistration($billablePayments)) {
            return 'Herregistrasi';
        }

        if (self::hasPaidRegistration($billablePayments) || ($lead->payment_status?->value ?? $lead->payment_status) === PaymentStatus::Paid->value) {
            return 'Registrasi';
        }

        return match ($lead->payment_status?->value ?? $lead->payment_status) {
            PaymentStatus::Unpaid->value => 'Belum Registrasi',
            PaymentStatus::Pending->value => 'Menunggu Registrasi',
            PaymentStatus::Expired->value => 'Tagihan Kadaluarsa',
            default => str((string) ($lead->payment_status?->value ?? $lead->payment_status ?? '-'))->replace('_', ' ')->title()->toString(),
        };
    }


    public static function statusColor(?string $state): string
    {
        $state = (string) $state;

        return match (true) {
            str_contains($state, 'Herregistrasi') && ! str_contains($state, 'Menunggu') && ! str_contains($state, 'Belum') => 'success',
            $state === 'Registrasi' => 'info',
            str_contains($state, 'Menunggu Registrasi') => 'warning',
            str_contains($state, 'Tagihan Kadaluarsa') || str_contains($state, 'Tagihan Kedaluwarsa') => 'danger',
            default => 'gray',
        };
    }

    public static function statusDotHtml(?string $state): HtmlString
    {
        $state = (string) ($state ?: '-');
        [$textColor, $backgroundColor, $borderColor] = match (self::statusColor($state)) {
            'success' => ['#15803d', '#f0fdf4', '#bbf7d0'],
            'info' => ['#2563eb', '#eff6ff', '#bfdbfe'],
            'warning' => ['#b45309', '#fffbeb', '#fde68a'],
            'danger' => ['#b91c1c', '#fef2f2', '#fecaca'],
            default => ['#475569', '#f8fafc', '#e2e8f0'],
        };
        $label = htmlspecialchars($state, ENT_QUOTES, 'UTF-8');

        return new HtmlString('<span style="display:inline-flex;align-items:center;border-radius:9999px;border:1px solid '.$borderColor.';background:'.$backgroundColor.';color:'.$textColor.';font-size:12px;font-weight:600;line-height:1;padding:4px 9px;white-space:nowrap;">'.$label.'</span>');
    }
    /**
     * @param  Collection<int, StudentPayment>  $payments
     */
    private static function hasPaidRegistration(Collection $payments): bool
    {
        return $payments->contains(fn (StudentPayment $payment): bool => (int) $payment->month === 0
            && in_array($payment->status, ['paid', 'waived'], true));
    }


    /**
     * @param  Collection<int, StudentPayment>  $payments
     */
    private static function hasPaidHerregistration(Collection $payments): bool
    {
        return $payments->contains(fn (StudentPayment $payment): bool => (int) $payment->month >= 1
            && in_array($payment->status, ['paid', 'waived'], true));
    }
}
