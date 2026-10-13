<?php

namespace App\Calendar;

use App\Models\Appointment;
use Carbon\CarbonImmutable;

/**
 * O calendário do celular da profissional.
 *
 * É a fonte da verdade sobre horários ocupados: o sistema lê o que está lá e
 * grava nele cada atendimento confirmado.
 */
interface CalendarGateway
{
    /** @return array<int, array{0: CarbonImmutable, 1: CarbonImmutable}> períodos ocupados por eventos que não são nossos */
    public function busyBetween(CarbonImmutable $from, CarbonImmutable $to): array;

    /** Cria ou atualiza o evento do atendimento e devolve o identificador dele no calendário. */
    public function save(Appointment $appointment): ?string;

    public function delete(Appointment $appointment): void;
}
