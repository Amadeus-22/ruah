<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\PatientPackage;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = CarbonImmutable::today();
        $with = ['patient', 'service', 'patientPackage.package'];

        $upcoming = Appointment::query()->with($with)
            ->whereIn('status', [AppointmentStatus::Confirmed, AppointmentStatus::Done, AppointmentStatus::NoShow])
            ->whereBetween('starts_at', [$today, $today->addDays(8)])
            ->orderBy('starts_at')->get()
            ->groupBy(fn (Appointment $a) => $a->starts_at->toDateString());

        return view('admin.dashboard', [
            'today' => $today,
            'pending' => Appointment::query()->with($with)
                ->where('status', AppointmentStatus::Requested)
                ->orderBy('starts_at')->get(),
            'upcoming' => $upcoming,
            // Confirmados que já passaram e ninguém marcou como realizado ou falta.
            'overdue' => Appointment::query()->with($with)
                ->where('status', AppointmentStatus::Confirmed)
                ->where('ends_at', '<', CarbonImmutable::now())
                ->orderBy('starts_at')->get(),
            'endingPackages' => PatientPackage::query()->with(['patient', 'package'])
                ->whereRaw('sessions_total - sessions_used between 1 and 2')
                ->get(),
        ]);
    }
}
