<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Service;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Serviços e pacotes: o que ela oferece. */
class CatalogController extends Controller
{
    public function index(): View
    {
        return view('admin.catalog', [
            'services' => Service::query()->orderBy('name')->get(),
            'packages' => Package::query()->with('service')->orderBy('name')->get(),
        ]);
    }

    public function saveService(Request $request, ?Service $service = null): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'price' => ['nullable', 'string', 'max:20'],
        ], [], ['name' => 'nome', 'duration_minutes' => 'duração']);

        Service::query()->updateOrCreate(['id' => $service?->id], [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'],
            'suggested_price_cents' => Money::parse($data['price'] ?? null),
            'active' => $request->boolean('active', true),
        ]);

        return back()->with('ok', 'Serviço salvo.');
    }

    public function savePackage(Request $request, ?Package $package = null): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'service_id' => ['required', 'exists:services,id'],
            'sessions' => ['required', 'integer', 'min:1', 'max:200'],
            'price' => ['nullable', 'string', 'max:20'],
        ], [], ['name' => 'nome', 'sessions' => 'sessões', 'service_id' => 'serviço']);

        Package::query()->updateOrCreate(['id' => $package?->id], [
            'name' => $data['name'],
            'service_id' => $data['service_id'],
            'sessions' => $data['sessions'],
            'price_cents' => Money::parse($data['price'] ?? null),
            'active' => $request->boolean('active', true),
        ]);

        return back()->with('ok', 'Pacote salvo.');
    }
}
