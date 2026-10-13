<?php

namespace App\Http\Controllers;

use App\Calendar\IcsFeed;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;

/** O endereço que o celular assina. Não tem login: quem protege é o código secreto no link. */
class CalendarFeedController extends Controller
{
    public function __invoke(string $token, IcsFeed $feed): Response
    {
        $expected = Setting::get('calendar_feed_token');
        abort_unless($expected !== '' && hash_equals($expected, $token), 404);

        $appointments = Appointment::query()->with(['patient', 'service'])
            ->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::Confirmed, AppointmentStatus::Done])
            ->where('starts_at', '>=', CarbonImmutable::today()->subDays(30))
            ->orderBy('starts_at')->get();

        return response($feed->render($appointments, Setting::get('clinic_name'), Setting::get('clinic_address')))
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Cache-Control', 'no-store');
    }
}
