<?php

namespace App\Support;

final class Money
{
    public static function format(?int $cents): string
    {
        return $cents === null ? '—' : 'R$ '.number_format($cents / 100, 2, ',', '.');
    }

    /** Aceita "150", "150,00", "1.250,50" e "R$ 150"; vazio vira null. */
    public static function parse(?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $clean = preg_replace('/[^\d,.]/', '', $value);
        if (str_contains($clean, ',')) {
            $clean = str_replace(',', '.', str_replace('.', '', $clean));
        }

        return is_numeric($clean) ? (int) round(((float) $clean) * 100) : null;
    }
}
