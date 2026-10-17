<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_saves_name_title_and_a_private_photo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['name' => 'Pamela Barbosa']);

        $this->actingAs($user)->put('/painel/perfil', [
            'name' => 'Pamela B.', 'title' => 'Fisioterapeuta respiratória', 'email' => $user->email,
            // PNG de 1×1 pronto: o ambiente de teste não tem GD para gerar imagem.
            'photo' => UploadedFile::fake()->createWithContent('eu.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=')),
        ])->assertSessionHas('ok');

        $user->refresh();
        $this->assertSame('Fisioterapeuta respiratória', $user->title);
        Storage::disk('local')->assertExists($user->avatar_path);
        $this->actingAs($user)->get('/painel/perfil/foto')->assertOk();
        $this->actingAs($user)->get('/painel')->assertSee('Fisioterapeuta respiratória')->assertSee('/painel/perfil/foto', false);

        $this->actingAs($user)->put('/painel/perfil', ['name' => 'Pamela B.', 'email' => $user->email, 'remove_photo' => '1']);
        $this->assertNull($user->refresh()->avatar_path);
        $this->actingAs($user)->get('/painel/perfil/foto')->assertNotFound();
    }

    public function test_without_a_photo_the_menu_shows_initials_and_gravatar(): void
    {
        $user = User::factory()->create(['name' => 'Pamela Barbosa']);

        $this->actingAs($user)->get('/painel')->assertSee('PB')->assertSee('gravatar.com/avatar/'.md5(strtolower($user->email)), false);
    }

    public function test_overview_compares_the_month_with_the_previous_one(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 12:00'));
        $user = User::factory()->create();
        $service = Service::create(['name' => 'Sessão de RTA', 'duration_minutes' => 50]);
        $patient = Patient::create(['guardian_name' => 'Ana', 'patient_name' => 'Bento', 'phone' => '21999990000']);
        $session = fn (string $at, ?int $paidCents, AppointmentStatus $status = AppointmentStatus::Done) => Appointment::create([
            'patient_id' => $patient->id, 'service_id' => $service->id, 'status' => $status,
            'starts_at' => $at, 'ends_at' => CarbonImmutable::parse($at)->addMinutes(50),
            'price_cents' => 15000, 'paid_at' => $paidCents ? $at : null, 'payment_method' => $paidCents ? 'pix' : null,
            'public_token' => Str::random(32),
        ]);
        $session('2026-09-10 09:00', 15000);
        $session('2026-10-05 09:00', 15000);
        $session('2026-10-06 09:00', 15000);
        $session('2026-10-07 09:00', null, AppointmentStatus::NoShow);

        $page = $this->actingAs($user)->get('/painel/visao?mes=2026-10')->assertOk()->assertSee('Pix');
        $kpis = $page->viewData('kpis');
        $this->assertSame(30000, $kpis['received']);
        $this->assertEqualsWithDelta(1.0, $kpis['received_change'], 0.001, 'o dobro de setembro');
        $this->assertSame(15000, $kpis['due'], 'a falta não paga');
        $this->assertSame(2, $kpis['done']);
        $this->assertEqualsWithDelta(1 / 3, $kpis['no_show_rate'], 0.001);
        $this->assertCount(6, $page->viewData('trend'));
    }
}
