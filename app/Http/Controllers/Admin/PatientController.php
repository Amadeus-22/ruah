<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Patient;
use App\Models\PatientPackage;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        return view('admin.patients.index', [
            'search' => $search,
            'patients' => Patient::query()
                ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                    ->where('patient_name', 'like', "%{$search}%")
                    ->orWhere('guardian_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', '%'.preg_replace('/\D+/', '', $search).'%')))
                ->withCount('appointments')
                ->orderBy('patient_name')->get(),
        ]);
    }

    public function show(Patient $patient): View
    {
        return view('admin.patients.show', [
            'patient' => $patient->load(['packages.package', 'appointments' => fn ($q) => $q->with('service')->orderByDesc('starts_at')]),
            'packages' => Package::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->validate([
            'guardian_name' => ['required', 'string', 'max:120'],
            'patient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]));

        return back()->with('ok', 'Cadastro atualizado.');
    }

    public function sellPackage(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
            'price' => ['nullable', 'string', 'max:20'],
            'paid' => ['nullable', 'boolean'],
        ]);
        $package = Package::query()->findOrFail($data['package_id']);

        PatientPackage::create([
            'patient_id' => $patient->id,
            'package_id' => $package->id,
            'sessions_total' => $package->sessions,
            'price_cents' => Money::parse($data['price'] ?? null) ?? $package->price_cents,
            'purchased_on' => today(),
            'paid_at' => $request->boolean('paid') ? now() : null,
        ]);

        return back()->with('ok', 'Pacote registrado para o paciente.');
    }
}
