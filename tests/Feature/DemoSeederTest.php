<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_fills_the_board_and_is_created_only_once(): void
    {
        $this->seed();
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame(3, Patient::count());
        $this->assertSame(6, Appointment::count());

        $user = User::query()->firstOrFail();
        $this->actingAs($user)->get('/painel')->assertOk()->assertSee('Marina Duarte')->assertSee('Davi');
        $this->actingAs($user)->get('/painel/financeiro')->assertOk();
    }
}
