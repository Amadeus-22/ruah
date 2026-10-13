<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AvailabilityRule;
use App\Models\Block;
use App\Models\Package;
use App\Models\Patient;
use App\Models\PatientPackage;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Scheduling\Booking;
use App\Scheduling\Schedule;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    private CarbonImmutable $monday;

    protected function setUp(): void
    {
        parent::setUp();
        // Uma segunda-feira fixa, vista do domingo anterior ao meio-dia.
        $this->monday = CarbonImmutable::parse('2026-10-19', config('app.timezone'));
        $this->travelTo($this->monday->subDay()->setTime(12, 0));

        $this->service = Service::create(['name' => 'Sessão de RTA', 'duration_minutes' => 50, 'suggested_price_cents' => 15000]);
        AvailabilityRule::create(['weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '12:00']);
        Setting::put('slot_step_minutes', '60');
        Setting::put('buffer_minutes', '10');
        Setting::put('min_notice_hours', '12');
    }

    private function patient(): Patient
    {
        return Patient::create(['guardian_name' => 'Ana', 'patient_name' => 'Bento', 'phone' => '21999990000']);
    }

    private function times(): array
    {
        return array_map(fn ($slot) => $slot->format('H:i'), app(Schedule::class)->slotsOn($this->service, $this->monday));
    }

    public function test_public_page_lists_services_and_free_slots(): void
    {
        $this->get('/')->assertOk()->assertSee('Sessão de RTA');
        $this->get('/agendar/'.$this->service->id)->assertOk()->assertSee('08:00')->assertSee('11:00');
    }

    public function test_a_request_holds_the_slot_and_waits_for_confirmation(): void
    {
        $response = $this->post('/agendar/'.$this->service->id, [
            'inicio' => '2026-10-19T09:00', 'guardian_name' => 'Ana', 'patient_name' => 'Bento',
            'phone' => '(21) 99999-0000', 'consent' => '1',
        ]);

        $appointment = Appointment::query()->firstOrFail();
        $response->assertRedirect('/pedido/'.$appointment->public_token);
        $this->assertSame(AppointmentStatus::Requested, $appointment->status);
        $this->assertSame('21999990000', $appointment->patient->phone);
        $this->assertSame(15000, $appointment->price_cents);
        $this->assertSame(['08:00', '10:00', '11:00'], $this->times());
        $this->get('/pedido/'.$appointment->public_token)->assertOk()->assertSee('Aguardando confirmação');
    }

    public function test_the_same_slot_cannot_be_requested_twice(): void
    {
        $data = ['inicio' => '2026-10-19T09:00', 'guardian_name' => 'Ana', 'patient_name' => 'Bento', 'phone' => '21999990000', 'consent' => '1'];
        $this->post('/agendar/'.$this->service->id, $data);

        $this->post('/agendar/'.$this->service->id, ['patient_name' => 'Caio'] + $data)
            ->assertRedirect('/agendar/'.$this->service->id)->assertSessionHas('error');
        $this->assertSame(1, Appointment::query()->count());
    }

    public function test_request_needs_consent_and_a_phone(): void
    {
        $this->post('/agendar/'.$this->service->id, ['inicio' => '2026-10-19T09:00', 'guardian_name' => 'Ana', 'patient_name' => 'Bento', 'phone' => '123'])
            ->assertSessionHasErrors(['phone', 'consent']);
        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_a_slot_outside_the_working_hours_is_refused(): void
    {
        $this->post('/agendar/'.$this->service->id, [
            'inicio' => '2026-10-19T15:00', 'guardian_name' => 'Ana', 'patient_name' => 'Bento', 'phone' => '21999990000', 'consent' => '1',
        ])->assertSessionHas('error');
        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_blocks_and_canceled_appointments_affect_free_slots(): void
    {
        Block::create(['starts_at' => $this->monday->setTime(8, 0), 'ends_at' => $this->monday->setTime(9, 30)]);
        $this->assertSame(['10:00', '11:00'], $this->times());

        $appointment = app(Booking::class)->book($this->patient(), $this->service, $this->monday->setTime(10, 0));
        $this->assertSame(['11:00'], $this->times());

        app(Booking::class)->cancel($appointment);
        $this->assertSame(['10:00', '11:00'], $this->times());
    }

    public function test_completing_an_appointment_uses_one_package_session(): void
    {
        $patient = $this->patient();
        $package = Package::create(['name' => '2 sessões', 'service_id' => $this->service->id, 'sessions' => 2, 'price_cents' => 28000]);
        $owned = PatientPackage::create(['patient_id' => $patient->id, 'package_id' => $package->id, 'sessions_total' => 2, 'purchased_on' => today()]);
        $booking = app(Booking::class);

        $first = $booking->book($patient, $this->service, $this->monday->setTime(8, 0), AppointmentStatus::Confirmed, package: $owned);
        $this->assertNull($first->price_cents);
        $booking->complete($first);
        $this->assertSame(1, $owned->fresh()->remaining());

        $second = $booking->book($patient, $this->service, $this->monday->setTime(10, 0), AppointmentStatus::Confirmed, package: $owned);
        $booking->noShow($second);
        $this->assertSame(0, $owned->fresh()->remaining(), 'falta desconta sessão por padrão');

        $this->expectException(DomainException::class);
        $booking->book($patient, $this->service, $this->monday->setTime(11, 0), AppointmentStatus::Confirmed, package: $owned->fresh());
    }

    public function test_no_show_keeps_the_session_when_the_rule_is_off(): void
    {
        Setting::put('no_show_uses_session', '0');
        $patient = $this->patient();
        $package = Package::create(['name' => '2 sessões', 'service_id' => $this->service->id, 'sessions' => 2]);
        $owned = PatientPackage::create(['patient_id' => $patient->id, 'package_id' => $package->id, 'sessions_total' => 2, 'purchased_on' => today()]);

        $appointment = app(Booking::class)->book($patient, $this->service, $this->monday->setTime(8, 0), AppointmentStatus::Confirmed, package: $owned);
        app(Booking::class)->noShow($appointment);

        $this->assertSame(2, $owned->fresh()->remaining());
    }

    public function test_an_appointment_cannot_skip_steps(): void
    {
        $appointment = app(Booking::class)->book($this->patient(), $this->service, $this->monday->setTime(8, 0));

        $this->expectException(DomainException::class);
        app(Booking::class)->complete($appointment); // ainda não foi confirmado
    }

    public function test_panel_requires_login_and_confirms_a_request(): void
    {
        $this->get('/painel')->assertRedirect('/entrar');

        $appointment = app(Booking::class)->book($this->patient(), $this->service, $this->monday->setTime(9, 0));
        $user = User::factory()->create();

        $this->actingAs($user)->get('/painel')->assertOk()->assertSee('Bento')->assertSee('Quer agendar');
        $this->actingAs($user)->get('/painel/agenda')->assertOk()->assertSee('Pedidos aguardando confirmação');
        $this->actingAs($user)->post("/painel/agendamentos/{$appointment->id}/confirmar")->assertSessionHas('ok');
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);
    }

    public function test_professional_books_directly_without_minimum_notice(): void
    {
        $user = User::factory()->create();
        $this->travelTo($this->monday->setTime(7, 30)); // mesma manhã: a página pública já não oferece

        $this->assertSame([], $this->times());
        $this->actingAs($user)->get('/painel/agendamentos/novo?day=2026-10-19')->assertOk()->assertSee('09:00');

        $this->actingAs($user)->post('/painel/agendamentos', [
            'service_id' => $this->service->id, 'start' => '2026-10-19T09:00',
            'guardian_name' => 'Ana', 'patient_name' => 'Bento', 'phone' => '21 99999-0000', 'price' => '120,00',
        ])->assertRedirect('/painel')->assertSessionHas('ok');

        $appointment = Appointment::query()->firstOrFail();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertSame(12000, $appointment->price_cents);
    }

    public function test_every_panel_page_renders(): void
    {
        $user = User::factory()->create();
        $patient = $this->patient();
        app(Booking::class)->book($patient, $this->service, $this->monday->setTime(8, 0), AppointmentStatus::Confirmed);

        foreach (['/painel', '/painel/agenda', '/painel/agendamentos/novo', '/painel/pacientes', '/painel/pacientes/'.$patient->id, '/painel/servicos', '/painel/configuracoes', '/painel/financeiro'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
        $this->get('/entrar')->assertOk();
    }

    public function test_a_contact_moves_through_the_board_and_leaves_when_booked(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/painel/contatos', ['guardian_name' => 'Dora', 'patient_name' => 'Elis', 'phone' => '21 98888-7777', 'channel' => 'instagram'])
            ->assertSessionHas('ok');
        $inquiry = \App\Models\Inquiry::query()->firstOrFail();
        $this->assertSame('new', $inquiry->stage);

        $this->actingAs($user)->post("/painel/contatos/{$inquiry->id}/etapa/waiting");
        $this->assertSame('waiting', $inquiry->fresh()->stage);
        $this->actingAs($user)->get('/painel')->assertOk()->assertSee('Dora')->assertSee('Aguardando paciente');

        // O formulário de novo horário já vem com os dados do contato.
        $this->actingAs($user)->get('/painel/agendamentos/novo?day=2026-10-19&inquiry_id='.$inquiry->id)->assertOk()->assertSee('Dora')->assertSee('Elis');

        $this->actingAs($user)->post('/painel/agendamentos', [
            'service_id' => $this->service->id, 'start' => '2026-10-19T10:00', 'inquiry_id' => $inquiry->id,
            'guardian_name' => 'Dora', 'patient_name' => 'Elis', 'phone' => '21 98888-7777',
        ])->assertSessionHas('ok');

        $this->assertNotNull($inquiry->fresh()->closed_at);
        $this->assertSame(AppointmentStatus::Confirmed, Appointment::query()->firstOrFail()->status);
    }

    public function test_a_contact_cannot_be_moved_to_an_unknown_stage(): void
    {
        $user = User::factory()->create();
        $inquiry = \App\Models\Inquiry::create(['guardian_name' => 'Dora']);

        $this->actingAs($user)->post("/painel/contatos/{$inquiry->id}/etapa/feito")->assertNotFound();
    }

    public function test_finance_separates_received_due_and_expected_and_records_the_invoice(): void
    {
        $user = User::factory()->create();
        $patient = $this->patient();
        $booking = app(Booking::class);
        $package = Package::create(['name' => '10 sessões', 'service_id' => $this->service->id, 'sessions' => 10, 'price_cents' => 130000]);
        $owned = PatientPackage::create(['patient_id' => $patient->id, 'package_id' => $package->id, 'sessions_total' => 10, 'price_cents' => 120000, 'purchased_on' => $this->monday]);

        $done = $booking->book($patient, $this->service, $this->monday->setTime(8, 0), AppointmentStatus::Confirmed, priceCents: 15000);
        $later = $booking->book($patient, $this->service, $this->monday->setTime(10, 0), AppointmentStatus::Confirmed, priceCents: 14000);
        $viaPackage = $booking->book($patient, $this->service, $this->monday->setTime(11, 0), AppointmentStatus::Confirmed, package: $owned);

        $this->travelTo($this->monday->setTime(9, 0)); // o das 8h já passou; os outros ainda não

        $this->actingAs($user)->put("/painel/financeiro/atendimento/{$done->id}", [
            'price' => '150,00', 'paid' => '1', 'payment_method' => 'pix', 'invoice_number' => 'NF-101',
        ])->assertSessionHas('ok');

        $done->refresh();
        $this->assertNotNull($done->paid_at);
        $this->assertSame('pix', $done->payment_method);
        $this->assertSame('NF-101', $done->invoice_number);

        $page = $this->actingAs($user)->get('/painel/financeiro?mes=2026-10')->assertOk();
        $totals = $page->viewData('totals');
        $this->assertSame(15000, $totals['received']);
        $this->assertSame(120000, $totals['due'], 'o pacote vendido e ainda não pago');
        $this->assertSame(14000, $totals['expected'], 'o atendimento que ainda vai acontecer');
        $this->assertSame(0, $totals['without_invoice']);
        // O atendimento feito pelo pacote não é um lançamento: o dinheiro é o do pacote.
        $this->assertFalse($page->viewData('entries')->contains(fn ($e) => $e['kind'] === 'atendimento' && $e['id'] === $viaPackage->id));

        $csv = $this->actingAs($user)->get('/painel/financeiro/exportar?mes=2026-10')->assertOk()->streamedContent();
        $this->assertStringContainsString('NF-101', $csv);
        $this->assertStringContainsString('150,00', $csv);
        $this->assertNotNull($later);
    }
}
