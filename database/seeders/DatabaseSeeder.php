<?php

namespace Database\Seeders;

use App\Models\AvailabilityRule;
use App\Models\Package;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Dados de exemplo para validar o sistema localmente. Serviços, valores e
     * horários são suposições: trocar pelos reais antes de usar.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'fisio@local.test'],
            ['name' => 'Fisioterapeuta', 'password' => Hash::make('trocar-esta-senha')],
        );

        Setting::put('clinic_name', 'Consultório de Fisioterapia (exemplo)');

        $avaliacao = Service::query()->firstOrCreate(['name' => 'Avaliação'], [
            'description' => 'Primeira consulta', 'duration_minutes' => 60, 'suggested_price_cents' => 20000,
        ]);
        $sessao = Service::query()->firstOrCreate(['name' => 'Sessão de RTA'], [
            'description' => 'Reequilíbrio Toracoabdominal', 'duration_minutes' => 50, 'suggested_price_cents' => 15000,
        ]);
        Package::query()->firstOrCreate(['name' => 'Pacote de 10 sessões de RTA'], [
            'service_id' => $sessao->id, 'sessions' => 10, 'price_cents' => 130000,
        ]);

        if (AvailabilityRule::query()->doesntExist()) {
            foreach ([1, 2, 3, 4, 5] as $weekday) {
                AvailabilityRule::create(['weekday' => $weekday, 'starts_at' => '08:00', 'ends_at' => '12:00']);
                AvailabilityRule::create(['weekday' => $weekday, 'starts_at' => '14:00', 'ends_at' => '18:00']);
            }
        }
    }
}
