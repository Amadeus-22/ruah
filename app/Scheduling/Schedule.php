<?php

namespace App\Scheduling;

use App\Calendar\CalendarGateway;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AvailabilityRule;
use App\Models\Block;
use App\Models\Service;
use App\Models\Setting;
use Carbon\CarbonImmutable;

/** Junta o que está no banco e no calendário e pergunta ao SlotFinder. */
final class Schedule
{
    public function __construct(private readonly CalendarGateway $calendar) {}

    /** @return array<int, CarbonImmutable> */
    public function slotsOn(Service $service, CarbonImmutable $day, ?CarbonImmutable $now = null, ?int $ignoreAppointmentId = null): array
    {
        $now ??= CarbonImmutable::now();
        $dayStart = $day->startOfDay();
        $dayEnd = $dayStart->addDay();

        $windows = AvailabilityRule::query()
            ->where('weekday', $dayStart->dayOfWeek)
            ->orderBy('starts_at')
            ->get()
            ->map(fn (AvailabilityRule $rule) => [
                $dayStart->setTimeFromTimeString($rule->starts_at),
                $dayStart->setTimeFromTimeString($rule->ends_at),
            ])
            ->all();

        if ($windows === []) {
            return [];
        }

        $holding = array_map(
            fn (AppointmentStatus $status) => $status->value,
            array_filter(AppointmentStatus::cases(), fn (AppointmentStatus $status) => $status->holdsSlot()),
        );

        $appointments = Appointment::query()
            ->whereIn('status', $holding)
            ->where('starts_at', '<', $dayEnd->addHours(2))
            ->where('ends_at', '>', $dayStart->subHours(2))
            ->when($ignoreAppointmentId, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
            ->get()
            ->map(fn (Appointment $a) => [$a->starts_at->toImmutable(), $a->ends_at->toImmutable()])
            ->all();

        $blocks = Block::query()
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $dayStart)
            ->get()
            ->map(fn (Block $b) => [$b->starts_at->toImmutable(), $b->ends_at->toImmutable()])
            ->all();

        // O que está no calendário do celular e não nasceu aqui também ocupa horário.
        $blocks = array_merge($blocks, $this->calendar->busyBetween($dayStart, $dayEnd));

        $finder = new SlotFinder(
            stepMinutes: max(Setting::int('slot_step_minutes'), 5),
            bufferMinutes: Setting::int('buffer_minutes'),
            minNoticeMinutes: Setting::int('min_notice_hours') * 60,
        );

        return $finder->find($service->duration_minutes, $windows, $appointments, $blocks, $now);
    }

    public function isFree(Service $service, CarbonImmutable $start, ?CarbonImmutable $now = null, ?int $ignoreAppointmentId = null): bool
    {
        foreach ($this->slotsOn($service, $start, $now, $ignoreAppointmentId) as $slot) {
            if ($slot->equalTo($start)) {
                return true;
            }
        }

        return false;
    }
}
