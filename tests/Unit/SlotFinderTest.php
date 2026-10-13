<?php

namespace Tests\Unit;

use App\Scheduling\SlotFinder;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class SlotFinderTest extends TestCase
{
    private function at(string $time): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-10-20 '.$time, 'America/Sao_Paulo');
    }

    /** @param array<int, CarbonImmutable> $slots */
    private function times(array $slots): array
    {
        return array_map(fn (CarbonImmutable $slot) => $slot->format('H:i'), $slots);
    }

    private function morning(): array
    {
        return [[$this->at('08:00'), $this->at('12:00')]];
    }

    private function yesterday(): CarbonImmutable
    {
        return $this->at('08:00')->subDay();
    }

    public function test_free_day_offers_every_step_that_fits_the_window(): void
    {
        $finder = new SlotFinder(stepMinutes: 30, bufferMinutes: 0, minNoticeMinutes: 0);

        $slots = $finder->find(60, $this->morning(), [], [], $this->yesterday());

        // 11:30 não entra: o atendimento de 60 min passaria das 12:00.
        $this->assertSame(['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00'], $this->times($slots));
    }

    public function test_an_appointment_removes_the_slots_it_overlaps(): void
    {
        $finder = new SlotFinder(stepMinutes: 30, bufferMinutes: 0, minNoticeMinutes: 0);

        $slots = $finder->find(60, $this->morning(), [[$this->at('09:00'), $this->at('10:00')]], [], $this->yesterday());

        // 08:00 termina quando o outro começa; 10:00 começa quando o outro termina.
        $this->assertSame(['08:00', '10:00', '10:30', '11:00'], $this->times($slots));
    }

    public function test_buffer_keeps_a_gap_on_both_sides_of_an_appointment(): void
    {
        $finder = new SlotFinder(stepMinutes: 30, bufferMinutes: 10, minNoticeMinutes: 0);

        $slots = $finder->find(60, $this->morning(), [[$this->at('09:30'), $this->at('10:30')]], [], $this->yesterday());

        // 08:30 terminaria às 09:30, colado no próximo; 10:30 começaria colado no anterior.
        $this->assertSame(['08:00', '11:00'], $this->times($slots));
    }

    public function test_buffer_does_not_apply_to_blocks(): void
    {
        $finder = new SlotFinder(stepMinutes: 30, bufferMinutes: 10, minNoticeMinutes: 0);

        $slots = $finder->find(60, $this->morning(), [], [[$this->at('09:00'), $this->at('10:00')]], $this->yesterday());

        $this->assertSame(['08:00', '10:00', '10:30', '11:00'], $this->times($slots));
    }

    public function test_minimum_notice_hides_slots_that_are_too_close(): void
    {
        $finder = new SlotFinder(stepMinutes: 30, bufferMinutes: 0, minNoticeMinutes: 120);

        $slots = $finder->find(60, $this->morning(), [], [], $this->at('07:45'));

        $this->assertSame(['10:00', '10:30', '11:00'], $this->times($slots));
    }

    public function test_a_service_longer_than_the_window_has_no_slots(): void
    {
        $finder = new SlotFinder(stepMinutes: 30, bufferMinutes: 0, minNoticeMinutes: 0);

        $this->assertSame([], $finder->find(300, $this->morning(), [], [], $this->yesterday()));
    }

    public function test_two_windows_in_a_day_are_both_used_and_sorted(): void
    {
        $finder = new SlotFinder(stepMinutes: 60, bufferMinutes: 0, minNoticeMinutes: 0);
        $windows = [[$this->at('14:00'), $this->at('16:00')], [$this->at('08:00'), $this->at('10:00')]];

        $slots = $finder->find(60, $windows, [], [], $this->yesterday());

        $this->assertSame(['08:00', '09:00', '14:00', '15:00'], $this->times($slots));
    }

    public function test_no_windows_means_no_slots(): void
    {
        $finder = new SlotFinder(stepMinutes: 30, bufferMinutes: 0, minNoticeMinutes: 0);

        $this->assertSame([], $finder->find(60, [], [], [], $this->yesterday()));
    }
}
