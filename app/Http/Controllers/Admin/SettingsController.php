<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityRule;
use App\Models\Block;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Horários de atendimento, bloqueios e regras da agenda. */
class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings', [
            'rules' => AvailabilityRule::query()->orderBy('weekday')->orderBy('starts_at')->get()->groupBy('weekday'),
            'blocks' => Block::query()->where('ends_at', '>=', now())->orderBy('starts_at')->get(),
            'settings' => collect(Setting::DEFAULTS)->map(fn ($default, $key) => Setting::get($key)),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'clinic_name' => ['required', 'string', 'max:120'],
            'clinic_address' => ['nullable', 'string', 'max:255'],
            'slot_step_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'buffer_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'min_notice_hours' => ['required', 'integer', 'min:0', 'max:720'],
            'max_days_ahead' => ['required', 'integer', 'min:1', 'max:180'],
        ]);
        foreach ($data as $key => $value) {
            Setting::put($key, (string) $value);
        }
        Setting::put('show_prices', $request->boolean('show_prices') ? '1' : '0');
        Setting::put('no_show_uses_session', $request->boolean('no_show_uses_session') ? '1' : '0');

        return back()->with('ok', 'Configurações salvas.');
    }

    public function addRule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'weekday' => ['required', 'integer', 'between:0,6'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ], ['ends_at.after' => 'O fim precisa ser depois do início.']);

        AvailabilityRule::create($data);

        return back()->with('ok', 'Horário de atendimento adicionado.');
    }

    public function removeRule(AvailabilityRule $rule): RedirectResponse
    {
        $rule->delete();

        return back()->with('ok', 'Horário removido.');
    }

    public function addBlock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:120'],
        ], ['ends_at.after' => 'O fim precisa ser depois do início.']);

        Block::create([
            'starts_at' => CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['starts_at']),
            'ends_at' => CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['ends_at']),
            'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('ok', 'Bloqueio adicionado.');
    }

    public function removeBlock(Block $block): RedirectResponse
    {
        $block->delete();

        return back()->with('ok', 'Bloqueio removido.');
    }
}
