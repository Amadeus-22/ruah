<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Requested = 'requested';
    case Confirmed = 'confirmed';
    case Done = 'done';
    case NoShow = 'no_show';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Aguardando confirmação',
            self::Confirmed => 'Confirmado',
            self::Done => 'Realizado',
            self::NoShow => 'Faltou',
            self::Canceled => 'Cancelado',
        };
    }

    /** O horário continua ocupado na agenda? */
    public function holdsSlot(): bool
    {
        return in_array($this, [self::Requested, self::Confirmed, self::Done, self::NoShow], true);
    }
}
