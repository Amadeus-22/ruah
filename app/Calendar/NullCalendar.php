<?php

namespace App\Calendar;

use App\Models\Appointment;
use Carbon\CarbonImmutable;

/**
 * Sem calendário ligado. É o que roda no desenvolvimento local, até a
 * sincronização com o iCloud (CalDAV) ser configurada.
 */
final class NullCalendar implements CalendarGateway
{
    public function busyBetween(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return [];
    }

    public function save(Appointment $appointment): ?string
    {
        return null;
    }

    public function delete(Appointment $appointment): void {}
}
