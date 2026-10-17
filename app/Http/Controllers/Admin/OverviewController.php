<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Finance\Ledger;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\PatientPackage;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Visão geral do consultório: dinheiro e atendimentos do mês, e a tendência dos últimos seis. */
class OverviewController extends Controller
{
    public function __invoke(Request $request): View
    {
        $month = $request->query('mes') && preg_match('/^\d{4}-\d{2}$/', $request->query('mes'))
            ? CarbonImmutable::createFromFormat('Y-m-d', $request->query('mes').'-01')->startOfDay()
            : CarbonImmutable::today()->startOfMonth();

        $entries = Ledger::entries($month);
        $previous = Ledger::entries($month->subMonth());
        $paid = $entries->where('paid', true);

        $received = $paid->sum('cents');
        $receivedBefore = $previous->where('paid', true)->sum('cents');

        $sessions = Appointment::query()
            ->whereIn('status', [AppointmentStatus::Done, AppointmentStatus::NoShow])
            ->where('starts_at', '>=', $month)->where('starts_at', '<', $month->addMonth())
            ->get(['status', 'patient_id']);
        $done = $sessions->where('status', AppointmentStatus::Done)->count();
        $noShow = $sessions->where('status', AppointmentStatus::NoShow)->count();

        // Seis meses terminando no mês escolhido, do mais antigo ao mais novo.
        $trend = collect(range(5, 0))->map(function (int $back) use ($month) {
            $m = $month->subMonths($back);
            $e = Ledger::entries($m);

            return [
                'label' => $m->translatedFormat('M'),
                'received' => $e->where('paid', true)->sum('cents'),
                'due' => $e->where('paid', false)->where('due', true)->sum('cents'),
            ];
        });

        $byMethod = $paid->groupBy(fn ($e) => $e['method'] ?: 'nao-informado')
            ->map(fn ($g, $method) => ['label' => FinanceController::METHODS[$method] ?? 'Não informado', 'cents' => $g->sum('cents')])
            ->sortByDesc('cents')->values();

        $byService = $entries->groupBy('description')
            ->map(fn ($g, $name) => ['label' => $name, 'cents' => $g->sum('cents'), 'count' => $g->count()])
            ->sortByDesc('cents')->take(5)->values();

        $owing = $entries->where('paid', false)->where('due', true)->sortBy('date')->take(5)->values();

        return view('admin.overview', [
            'month' => $month,
            'kpis' => [
                'received' => $received,
                'received_change' => $receivedBefore > 0 ? ($received - $receivedBefore) / $receivedBefore : null,
                'due' => $entries->where('paid', false)->where('due', true)->sum('cents'),
                'expected' => $entries->where('paid', false)->where('due', false)->sum('cents'),
                'ticket' => $paid->count() > 0 ? intdiv($received, $paid->count()) : 0,
                'done' => $done,
                'no_show_rate' => ($done + $noShow) > 0 ? $noShow / ($done + $noShow) : null,
                'patients' => $sessions->where('status', AppointmentStatus::Done)->pluck('patient_id')->unique()->count(),
                'package_sessions_left' => (int) PatientPackage::query()->selectRaw('coalesce(sum(sessions_total - sessions_used), 0) as left_over')->value('left_over'),
            ],
            'trend' => $trend,
            'byMethod' => $byMethod,
            'byService' => $byService,
            'owing' => $owing,
        ]);
    }
}
