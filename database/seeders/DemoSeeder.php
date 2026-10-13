<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\Patient;
use App\Models\PatientPackage;
use App\Models\Service;
use App\Models\Setting;
use App\Scheduling\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Pessoas e atendimentos fictícios, para ver o quadro e o financeiro preenchidos.
 * Roda depois do DatabaseSeeder: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(Booking $booking): void
    {
        if (Patient::query()->exists() || Inquiry::query()->exists()) {
            $this->command?->warn('Já existem pacientes ou contatos; nada foi criado.');

            return;
        }

        Setting::put('clinic_name', 'Ruah Fisioterapia');
        Setting::put('clinic_address', 'Rua das Acácias, 120, sala 4');

        $rta = Service::query()->where('name', 'Sessão de RTA')->firstOrFail();
        $evaluation = Service::query()->where('name', 'Avaliação')->firstOrFail();

        Inquiry::create(['guardian_name' => 'Marina Duarte', 'patient_name' => 'Theo', 'phone' => '21999990001', 'channel' => 'instagram', 'note' => 'Prefere de manhã']);
        Inquiry::create(['guardian_name' => 'Carla Nunes', 'phone' => '21999990002', 'channel' => 'whatsapp', 'stage' => 'waiting', 'note' => 'Ofereci terça 9h ou quinta 15h']);

        $bento = Patient::create(['guardian_name' => 'Ana Prado', 'patient_name' => 'Bento', 'phone' => '21999990003']);
        $lia = Patient::create(['guardian_name' => 'Júlia Farias', 'patient_name' => 'Lia', 'phone' => '21999990004']);
        $davi = Patient::create(['guardian_name' => 'Rafael Lins', 'patient_name' => 'Davi', 'phone' => '21999990005']);

        $package = PatientPackage::create([
            'patient_id' => $lia->id,
            'package_id' => Package::query()->firstOrFail()->id,
            'sessions_total' => 10,
            'sessions_used' => 8,
            'price_cents' => 130000,
            'purchased_on' => CarbonImmutable::today()->subDays(20),
            'paid_at' => CarbonImmutable::now()->subDays(20),
            'payment_method' => 'pix',
        ]);

        $booking->book($bento, $evaluation, $this->weekday(2)->setTime(9, 0), AppointmentStatus::Requested, 'Primeira vez');
        $booking->book($lia, $rta, $this->weekday(1)->setTime(10, 0), AppointmentStatus::Confirmed, package: $package, ignoreNotice: true);
        $booking->book($davi, $rta, $this->weekday(3)->setTime(15, 0), AppointmentStatus::Confirmed, priceCents: 14000, ignoreNotice: true);

        // O que já passou entra direto, sem passar pela regra de horários livres.
        foreach ([
            [$bento, 2, 9, AppointmentStatus::Done, true],
            [$davi, 3, 14, AppointmentStatus::Done, false],
            [$bento, 5, 11, AppointmentStatus::NoShow, false],
        ] as [$patient, $daysAgo, $hour, $status, $paid]) {
            $start = $this->weekday(-$daysAgo)->setTime($hour, 0);
            Appointment::create([
                'patient_id' => $patient->id,
                'service_id' => $rta->id,
                'starts_at' => $start,
                'ends_at' => $start->addMinutes($rta->duration_minutes),
                'status' => $status,
                'price_cents' => 15000,
                'paid_at' => $paid ? $start : null,
                'payment_method' => $paid ? 'pix' : null,
                'public_token' => Str::random(32),
            ]);
        }
    }

    /** O dia a tantos dias de hoje, empurrado para fora do fim de semana. */
    private function weekday(int $offset): CarbonImmutable
    {
        $day = CarbonImmutable::today()->addDays($offset);
        while ($day->isWeekend()) {
            $day = $offset < 0 ? $day->subDay() : $day->addDay();
        }

        return $day;
    }
}
