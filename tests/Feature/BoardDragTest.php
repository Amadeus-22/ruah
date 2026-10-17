<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\AvailabilityRule;
use App\Models\Inquiry;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Scheduling\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardDragTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Service $service;

    private Patient $patient;

    private CarbonImmutable $monday;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monday = CarbonImmutable::parse('2026-10-19', config('app.timezone'));
        $this->travelTo($this->monday->subDay()->setTime(12, 0));
        $this->user = User::factory()->create();
        $this->service = Service::create(['name' => 'Sessão de RTA', 'duration_minutes' => 50]);
        $this->patient = Patient::create(['guardian_name' => 'Ana', 'patient_name' => 'Bento', 'phone' => '21999990000']);
        AvailabilityRule::create(['weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '12:00']);
        Setting::put('min_notice_hours', '0');
    }

    private function move(string $kind, int $id, string $to)
    {
        return $this->actingAs($this->user)->postJson('/painel/quadro/mover', ['kind' => $kind, 'id' => $id, 'to' => $to]);
    }

    public function test_a_contact_is_dragged_to_waiting_and_back(): void
    {
        $inquiry = Inquiry::create(['guardian_name' => 'Marina', 'channel' => 'instagram', 'stage' => Inquiry::STAGE_NEW]);

        $this->move('contato', $inquiry->id, 'aguardando')->assertOk();
        $this->assertSame(Inquiry::STAGE_WAITING, $inquiry->refresh()->stage);

        $this->move('contato', $inquiry->id, 'novo')->assertOk();
        $this->assertSame(Inquiry::STAGE_NEW, $inquiry->refresh()->stage);
    }

    public function test_dropping_a_contact_on_booked_opens_the_prefilled_form(): void
    {
        $inquiry = Inquiry::create(['guardian_name' => 'Marina', 'channel' => 'whatsapp', 'stage' => Inquiry::STAGE_WAITING]);

        $this->move('contato', $inquiry->id, 'agendado')->assertOk()
            ->assertJsonPath('redirect', route('admin.appointments.create', ['inquiry_id' => $inquiry->id]));
        $this->move('contato', $inquiry->id, 'realizado')->assertStatus(422);
    }

    public function test_appointments_follow_the_booking_rules(): void
    {
        $booking = app(Booking::class);
        $requested = $booking->book($this->patient, $this->service, $this->monday->setTime(8, 0), AppointmentStatus::Requested);

        $this->move('agendamento', $requested->id, 'realizado')->assertStatus(422)->assertJsonPath('message', 'Confirme o pedido arrastando para "Agendado".');
        $this->move('agendamento', $requested->id, 'agendado')->assertOk();
        $this->assertSame(AppointmentStatus::Confirmed, $requested->refresh()->status);

        // Antes do horário, não dá para marcar realizado nem falta.
        $this->move('agendamento', $requested->id, 'realizado')->assertStatus(422)->assertJsonPath('message', 'Esse atendimento ainda não aconteceu.');

        $this->travelTo($this->monday->setTime(9, 0));
        $this->move('agendamento', $requested->id, 'faltou')->assertOk();
        $this->assertSame(AppointmentStatus::NoShow, $requested->refresh()->status);
        $this->move('agendamento', $requested->id, 'agendado')->assertStatus(422);
    }

    public function test_the_board_marks_only_movable_cards_as_draggable(): void
    {
        Inquiry::create(['guardian_name' => 'Marina', 'channel' => 'instagram', 'stage' => Inquiry::STAGE_NEW]);
        app(Booking::class)->book($this->patient, $this->service, $this->monday->setTime(10, 0), AppointmentStatus::Confirmed);

        $this->actingAs($this->user)->get('/painel')->assertOk()
            ->assertSee('data-allowed="aguardando agendado"', false)
            ->assertSee('data-kind="agendamento"', false)
            ->assertSee('data-col="faltou"', false);
    }
}
