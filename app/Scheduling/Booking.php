<?php

namespace App\Scheduling;

use App\Calendar\CalendarGateway;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientPackage;
use App\Models\Service;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** O ciclo de vida de um agendamento: pedido, confirmação, realização, falta, cancelamento. */
final class Booking
{
    public function __construct(
        private readonly Schedule $schedule,
        private readonly CalendarGateway $calendar,
    ) {}

    /**
     * Registra um horário. Pedidos vindos da página pública entram como "aguardando";
     * os lançados pela profissional já entram confirmados e ignoram a antecedência mínima.
     */
    public function book(
        Patient $patient,
        Service $service,
        CarbonImmutable $start,
        AppointmentStatus $status = AppointmentStatus::Requested,
        ?string $note = null,
        ?int $priceCents = null,
        ?PatientPackage $package = null,
        bool $ignoreNotice = false,
    ): Appointment {
        // Uma trava só para a agenda inteira: conferir o horário e gravar têm de
        // acontecer juntos, senão dois pedidos simultâneos ficam com o mesmo horário.
        return Cache::lock('agenda:booking', 10)->block(5, fn () => DB::transaction(function () use ($patient, $service, $start, $status, $note, $priceCents, $package, $ignoreNotice) {
            // A profissional pode marcar em cima da hora; a antecedência mínima
            // vale só para pedidos da página pública.
            $now = $ignoreNotice ? $start->subYears(10) : null;
            if (! $this->schedule->isFree($service, $start, $now)) {
                throw new DomainException('Esse horário acabou de ser ocupado. Escolha outro.');
            }
            if ($package && ($package->patient_id !== $patient->id || $package->remaining() < 1)) {
                throw new DomainException('O pacote escolhido não tem sessões disponíveis para este paciente.');
            }

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'service_id' => $service->id,
                'patient_package_id' => $package?->id,
                'starts_at' => $start,
                'ends_at' => $start->addMinutes($service->duration_minutes),
                'status' => $status,
                'price_cents' => $package ? null : ($priceCents ?? $service->suggested_price_cents),
                'request_note' => $note,
                'public_token' => Str::random(32),
            ]);

            if ($status === AppointmentStatus::Confirmed) {
                $this->syncCalendar($appointment);
            }

            return $appointment;
        }));
    }

    public function confirm(Appointment $appointment): void
    {
        $this->expect($appointment, AppointmentStatus::Requested);
        $appointment->update(['status' => AppointmentStatus::Confirmed]);
        $this->syncCalendar($appointment);
    }

    public function cancel(Appointment $appointment): void
    {
        if (in_array($appointment->status, [AppointmentStatus::Done, AppointmentStatus::Canceled], true)) {
            throw new DomainException('Este agendamento não pode mais ser cancelado.');
        }
        DB::transaction(function () use ($appointment) {
            // Uma falta que tinha descontado sessão devolve a sessão ao ser cancelada.
            if ($appointment->status === AppointmentStatus::NoShow && Setting::bool('no_show_uses_session')) {
                $this->refundSession($appointment);
            }
            $appointment->update(['status' => AppointmentStatus::Canceled]);
        });
        $this->calendar->delete($appointment);
    }

    public function complete(Appointment $appointment): void
    {
        $this->expect($appointment, AppointmentStatus::Confirmed);
        DB::transaction(function () use ($appointment) {
            $this->useSession($appointment);
            $appointment->update(['status' => AppointmentStatus::Done]);
        });
    }

    public function noShow(Appointment $appointment): void
    {
        $this->expect($appointment, AppointmentStatus::Confirmed);
        DB::transaction(function () use ($appointment) {
            if (Setting::bool('no_show_uses_session')) {
                $this->useSession($appointment);
            }
            $appointment->update(['status' => AppointmentStatus::NoShow]);
        });
    }

    private function useSession(Appointment $appointment): void
    {
        if (! $appointment->patient_package_id) {
            return;
        }
        $package = PatientPackage::query()->lockForUpdate()->find($appointment->patient_package_id);
        if (! $package || $package->remaining() < 1) {
            throw new DomainException('O pacote deste paciente não tem mais sessões.');
        }
        $package->increment('sessions_used');
    }

    private function refundSession(Appointment $appointment): void
    {
        if (! $appointment->patient_package_id) {
            return;
        }
        $package = PatientPackage::query()->lockForUpdate()->find($appointment->patient_package_id);
        if ($package && $package->sessions_used > 0) {
            $package->decrement('sessions_used');
        }
    }

    private function expect(Appointment $appointment, AppointmentStatus $status): void
    {
        if ($appointment->status !== $status) {
            throw new DomainException('Este agendamento está como "'.$appointment->status->label().'" e não permite essa ação.');
        }
    }

    private function syncCalendar(Appointment $appointment): void
    {
        $eventId = $this->calendar->save($appointment);
        if ($eventId !== null && $eventId !== $appointment->calendar_event_id) {
            $appointment->update(['calendar_event_id' => $eventId]);
        }
    }
}
