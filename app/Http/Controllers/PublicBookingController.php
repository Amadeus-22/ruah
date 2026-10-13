<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Setting;
use App\Scheduling\Booking;
use App\Scheduling\Schedule;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** A página que o responsável abre pelo link do WhatsApp ou do Instagram. */
class PublicBookingController extends Controller
{
    public function index(): View
    {
        return view('public.index', [
            'services' => Service::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function slots(Service $service, Schedule $schedule): View
    {
        abort_unless($service->active, 404);

        $today = CarbonImmutable::today();
        $days = [];
        for ($i = 0; $i <= Setting::int('max_days_ahead'); $i++) {
            $day = $today->addDays($i);
            $slots = $schedule->slotsOn($service, $day);
            if ($slots !== []) {
                $days[] = ['day' => $day, 'slots' => $slots];
            }
        }

        return view('public.slots', ['service' => $service, 'days' => $days]);
    }

    public function form(Request $request, Service $service, Schedule $schedule): View|RedirectResponse
    {
        abort_unless($service->active, 404);
        $start = $this->parseStart($request->query('inicio'));

        if (! $start || ! $schedule->isFree($service, $start)) {
            return redirect()->route('public.slots', $service)->with('error', 'Esse horário não está mais disponível. Escolha outro.');
        }

        return view('public.form', ['service' => $service, 'start' => $start]);
    }

    public function store(Request $request, Service $service, Booking $booking): RedirectResponse
    {
        abort_unless($service->active, 404);

        $data = $request->validate([
            'inicio' => ['required', 'string'],
            'guardian_name' => ['required', 'string', 'max:120'],
            'patient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^[\d\s()+\-]{10,20}$/'],
            'note' => ['nullable', 'string', 'max:500'],
            'consent' => ['accepted'],
        ], [
            'phone.regex' => 'Informe o telefone com DDD.',
            'consent.accepted' => 'É preciso concordar com o uso dos dados para o agendamento.',
        ], [
            'guardian_name' => 'nome do responsável',
            'patient_name' => 'nome do paciente',
            'phone' => 'telefone',
            'note' => 'observação',
        ]);

        $start = $this->parseStart($data['inicio']);
        if (! $start) {
            return redirect()->route('public.slots', $service)->with('error', 'Horário inválido. Escolha novamente.');
        }

        // Mesmo telefone e mesmo paciente: é o mesmo cadastro.
        $phone = preg_replace('/\D+/', '', $data['phone']);
        $patient = Patient::query()->firstOrCreate(
            ['phone' => $phone, 'patient_name' => trim($data['patient_name'])],
            ['guardian_name' => trim($data['guardian_name'])],
        );

        try {
            $appointment = $booking->book($patient, $service, $start, AppointmentStatus::Requested, $data['note'] ?? null);
        } catch (DomainException $e) {
            return redirect()->route('public.slots', $service)->with('error', $e->getMessage());
        }

        return redirect()->route('public.show', $appointment->public_token);
    }

    public function show(string $token): View
    {
        $appointment = Appointment::query()->where('public_token', $token)->with(['service', 'patient'])->firstOrFail();

        return view('public.show', ['appointment' => $appointment]);
    }

    private function parseStart(?string $value): ?CarbonImmutable
    {
        if (! $value || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d\TH:i', $value)->startOfMinute();
    }
}
