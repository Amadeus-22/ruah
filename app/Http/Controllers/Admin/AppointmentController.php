<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Inquiry;
use App\Models\Patient;
use App\Models\PatientPackage;
use App\Models\Service;
use App\Scheduling\Booking;
use App\Scheduling\Schedule;
use App\Support\Money;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    /** Formulário para a profissional lançar um horário combinado por mensagem. */
    public function create(Request $request, Schedule $schedule): View
    {
        $services = Service::query()->where('active', true)->orderBy('name')->get();
        $service = $services->firstWhere('id', (int) $request->query('service_id')) ?? $services->first();
        $day = $this->parseDay($request->query('day')) ?? CarbonImmutable::today();
        $patient = Patient::query()->with('packages.package')->find($request->query('patient_id'));

        return view('admin.appointments.create', [
            'services' => $services,
            'service' => $service,
            'day' => $day,
            'patient' => $patient,
            'patients' => Patient::query()->orderBy('patient_name')->get(),
            'inquiry' => Inquiry::query()->whereNull('closed_at')->find($request->query('inquiry_id')),
            // Ela pode marcar em cima da hora: sem antecedência mínima aqui.
            'slots' => $service ? $schedule->slotsOn($service, $day, CarbonImmutable::today()->subYears(10)) : [],
        ]);
    }

    public function store(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'start' => ['required', 'date_format:Y-m-d\TH:i'],
            'patient_id' => ['nullable', 'exists:patients,id'],
            'guardian_name' => ['required_without:patient_id', 'nullable', 'string', 'max:120'],
            'patient_name' => ['required_without:patient_id', 'nullable', 'string', 'max:120'],
            'phone' => ['required_without:patient_id', 'nullable', 'string', 'max:20'],
            'patient_package_id' => ['nullable', 'exists:patient_packages,id'],
            'price' => ['nullable', 'string', 'max:20'],
            'inquiry_id' => ['nullable', 'exists:inquiries,id'],
        ], [], ['guardian_name' => 'nome do responsável', 'patient_name' => 'nome do paciente', 'phone' => 'telefone']);

        $patient = ! empty($data['patient_id'])
            ? Patient::query()->findOrFail($data['patient_id'])
            : Patient::query()->firstOrCreate(
                ['phone' => preg_replace('/\D+/', '', $data['phone']), 'patient_name' => trim($data['patient_name'])],
                ['guardian_name' => trim($data['guardian_name'])],
            );

        try {
            $booking->book(
                $patient,
                Service::query()->findOrFail($data['service_id']),
                CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['start'])->startOfMinute(),
                AppointmentStatus::Confirmed,
                priceCents: Money::parse($data['price'] ?? null),
                package: ! empty($data['patient_package_id']) ? PatientPackage::query()->find($data['patient_package_id']) : null,
                ignoreNotice: true,
            );
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // O contato que virou agendamento sai da triagem.
        if (! empty($data['inquiry_id'])) {
            Inquiry::query()->whereKey($data['inquiry_id'])->update(['closed_at' => now()]);
        }

        return redirect()->route('admin.dashboard')->with('ok', 'Agendamento criado.');
    }

    public function action(Appointment $appointment, string $action, Booking $booking): RedirectResponse
    {
        try {
            match ($action) {
                'confirmar' => $booking->confirm($appointment),
                'cancelar' => $booking->cancel($appointment),
                'realizado' => $booking->complete($appointment),
                'faltou' => $booking->noShow($appointment),
                default => abort(404),
            };
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', 'Agendamento atualizado.');
    }

    /** Marca o lembrete como enviado e abre o WhatsApp com a mensagem pronta. */
    public function remind(Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->status === AppointmentStatus::Confirmed, 404);
        $appointment->update(['reminded_at' => now()]);

        return redirect()->away($appointment->reminderLink());
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate([
            'price' => ['nullable', 'string', 'max:20'],
            'paid' => ['nullable', 'boolean'],
        ]);

        $appointment->update([
            'price_cents' => Money::parse($data['price'] ?? null),
            'paid_at' => $request->boolean('paid') ? ($appointment->paid_at ?? now()) : null,
        ]);

        return back()->with('ok', 'Valor atualizado.');
    }

    private function parseDay(?string $value): ?CarbonImmutable
    {
        return $value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? CarbonImmutable::parse($value)->startOfDay() : null;
    }
}
