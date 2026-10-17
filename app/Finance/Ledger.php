<?php

namespace App\Finance;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\PatientPackage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Os lançamentos de um mês: atendimentos avulsos e pacotes vendidos. */
class Ledger
{
    /** @return Collection<int, array<string, mixed>> */
    public static function entries(CarbonImmutable $month): Collection
    {
        $from = $month->startOfMonth();
        $to = $from->addMonth();
        $now = CarbonImmutable::now();

        // Atendimento de pacote não entra: o dinheiro entrou na venda do pacote.
        $appointments = Appointment::query()->with(['patient', 'service'])
            ->whereNull('patient_package_id')
            ->whereNotIn('status', [AppointmentStatus::Canceled, AppointmentStatus::Requested])
            ->where('starts_at', '>=', $from)->where('starts_at', '<', $to)
            ->get()
            ->map(fn (Appointment $a) => [
                'kind' => 'atendimento', 'id' => $a->id, 'date' => $a->starts_at->toImmutable(),
                'patient' => $a->patient->patient_name, 'guardian' => $a->patient->guardian_name, 'patient_id' => $a->patient_id,
                'description' => $a->service->name.($a->status === AppointmentStatus::NoShow ? ' (faltou)' : ''),
                'cents' => (int) $a->price_cents, 'has_price' => $a->price_cents !== null,
                'paid' => $a->paid_at !== null, 'paid_at' => $a->paid_at, 'method' => $a->payment_method,
                'invoice' => $a->invoice_number, 'invoiced' => $a->invoice_number !== null,
                // Só é "a receber" o que já aconteceu; o que está por vir é "previsto".
                'due' => $a->starts_at->lessThanOrEqualTo($now),
            ]);

        $packages = PatientPackage::query()->with(['patient', 'package'])
            ->where('purchased_on', '>=', $from->toDateString())->where('purchased_on', '<', $to->toDateString())
            ->get()
            ->map(fn (PatientPackage $p) => [
                'kind' => 'pacote', 'id' => $p->id, 'date' => $p->purchased_on->toImmutable(),
                'patient' => $p->patient->patient_name, 'guardian' => $p->patient->guardian_name, 'patient_id' => $p->patient_id,
                'description' => $p->package->name,
                'cents' => (int) $p->price_cents, 'has_price' => $p->price_cents !== null,
                'paid' => $p->paid_at !== null, 'paid_at' => $p->paid_at, 'method' => $p->payment_method,
                'invoice' => $p->invoice_number, 'invoiced' => $p->invoice_number !== null,
                'due' => true,
            ]);

        return $appointments->concat($packages)->sortBy('date')->values();
    }
}
