<?php

namespace App\Scheduling;

use Carbon\CarbonImmutable;

/**
 * Calcula os horários em que um atendimento pode começar.
 *
 * Não lê banco nem relógio: recebe as janelas de atendimento, os períodos
 * ocupados e o "agora". Assim a regra é testada sozinha, sem banco.
 */
final class SlotFinder
{
    public function __construct(
        private readonly int $stepMinutes,
        private readonly int $bufferMinutes,
        private readonly int $minNoticeMinutes,
    ) {}

    /**
     * @param  array<int, array{0: CarbonImmutable, 1: CarbonImmutable}>  $windows  janelas de atendimento do dia
     * @param  array<int, array{0: CarbonImmutable, 1: CarbonImmutable}>  $appointments  atendimentos que seguram horário
     * @param  array<int, array{0: CarbonImmutable, 1: CarbonImmutable}>  $blocks  bloqueios (compromissos pessoais, folgas)
     * @return array<int, CarbonImmutable> inícios possíveis, em ordem
     */
    public function find(int $durationMinutes, array $windows, array $appointments, array $blocks, CarbonImmutable $now): array
    {
        $earliest = $now->addMinutes($this->minNoticeMinutes);

        // O intervalo entre pacientes vale dos dois lados de cada atendimento.
        $busy = $blocks;
        foreach ($appointments as [$start, $end]) {
            $busy[] = [$start->subMinutes($this->bufferMinutes), $end->addMinutes($this->bufferMinutes)];
        }

        $slots = [];
        foreach ($windows as [$open, $close]) {
            for ($start = $open; ; $start = $start->addMinutes($this->stepMinutes)) {
                $end = $start->addMinutes($durationMinutes);
                if ($end->greaterThan($close)) {
                    break;
                }
                if ($start->lessThan($earliest) || $this->overlapsAny($start, $end, $busy)) {
                    continue;
                }
                $slots[$start->getTimestamp()] = $start;
            }
        }

        ksort($slots);

        return array_values($slots);
    }

    /** @param array<int, array{0: CarbonImmutable, 1: CarbonImmutable}> $intervals */
    private function overlapsAny(CarbonImmutable $start, CarbonImmutable $end, array $intervals): bool
    {
        foreach ($intervals as [$busyStart, $busyEnd]) {
            // Encostar não é sobrepor: um atendimento pode começar quando o outro termina.
            if ($start->lessThan($busyEnd) && $end->greaterThan($busyStart)) {
                return true;
            }
        }

        return false;
    }
}
