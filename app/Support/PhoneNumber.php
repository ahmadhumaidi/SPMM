<?php

namespace App\Support;

class PhoneNumber
{
    public static function normalizeWhatsapp(string $number, string $countryCode = '62'): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (str_starts_with($digits, '0')) {
            return $countryCode.substr($digits, 1);
        }

        if (str_starts_with($digits, $countryCode)) {
            return $digits;
        }

        return $countryCode.$digits;
    }
}
