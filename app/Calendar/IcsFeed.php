<?php

namespace App\Calendar;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Collection;

/**
 * Agenda em formato iCalendar, para o celular assinar. É só de leitura:
 * o que muda no sistema aparece no celular, não o contrário.
 */
class IcsFeed
{
    /** @param Collection<int, Appointment> $appointments */
    public function render(Collection $appointments, string $calendarName, string $address = ''): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//ruah//PT-BR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escape($calendarName),
            'X-PUBLISHED-TTL:PT15M',
        ];

        foreach ($appointments as $appointment) {
            $title = $appointment->patient->patient_name.' · '.$appointment->service->name;
            if ($appointment->status === AppointmentStatus::Requested) {
                $title = 'Pedido: '.$title;
            }

            array_push(
                $lines,
                'BEGIN:VEVENT',
                'UID:agendamento-'.$appointment->id.'@ruah',
                'DTSTAMP:'.$this->utc($appointment->updated_at),
                'DTSTART:'.$this->utc($appointment->starts_at),
                'DTEND:'.$this->utc($appointment->ends_at),
                'SUMMARY:'.$this->escape($title),
                'DESCRIPTION:'.$this->escape('Responsável: '.$appointment->patient->guardian_name.' · '.$appointment->patient->phone),
                'STATUS:'.($appointment->status === AppointmentStatus::Requested ? 'TENTATIVE' : 'CONFIRMED'),
            );
            if ($address !== '') {
                $lines[] = 'LOCATION:'.$this->escape($address);
            }
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map($this->fold(...), $lines))."\r\n";
    }

    private function utc(\DateTimeInterface $moment): string
    {
        return gmdate('Ymd\THis\Z', $moment->getTimestamp());
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }

    /** A norma pede linhas de até 75 octetos; o que passa continua na linha seguinte, com um espaço. */
    private function fold(string $line): string
    {
        $parts = [];
        while (strlen($line) > 75) {
            $cut = mb_strcut($line, 0, 73, 'UTF-8');
            $parts[] = $cut;
            $line = substr($line, strlen($cut));
        }
        $parts[] = $line;

        return implode("\r\n ", $parts);
    }
}
