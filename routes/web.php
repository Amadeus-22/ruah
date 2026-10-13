<?php

use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\PublicBookingController;
use Illuminate\Support\Facades\Route;

// Página pública: o link que vai na bio do Instagram e no WhatsApp.
Route::get('/', [PublicBookingController::class, 'index'])->name('public.index');
Route::get('/agendar/{service}', [PublicBookingController::class, 'slots'])->name('public.slots');
Route::get('/agendar/{service}/dados', [PublicBookingController::class, 'form'])->name('public.form');
Route::post('/agendar/{service}', [PublicBookingController::class, 'store'])->middleware('throttle:10,1')->name('public.store');
Route::get('/pedido/{token}', [PublicBookingController::class, 'show'])->name('public.show');

Route::get('/entrar', [AuthController::class, 'form'])->name('login');
Route::post('/entrar', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/sair', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->prefix('painel')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/agendamentos/novo', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/agendamentos', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::post('/agendamentos/{appointment}/{action}', [AppointmentController::class, 'action'])->name('appointments.action');
    Route::put('/agendamentos/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');

    Route::get('/pacientes', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/pacientes/{patient}', [PatientController::class, 'show'])->name('patients.show');
    Route::put('/pacientes/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::post('/pacientes/{patient}/pacotes', [PatientController::class, 'sellPackage'])->name('patients.packages');

    Route::get('/servicos', [CatalogController::class, 'index'])->name('catalog');
    Route::post('/servicos/{service?}', [CatalogController::class, 'saveService'])->name('services.save');
    Route::post('/pacotes/{package?}', [CatalogController::class, 'savePackage'])->name('packages.save');

    Route::get('/configuracoes', [SettingsController::class, 'index'])->name('settings');
    Route::post('/configuracoes', [SettingsController::class, 'save'])->name('settings.save');
    Route::post('/horarios', [SettingsController::class, 'addRule'])->name('rules.add');
    Route::delete('/horarios/{rule}', [SettingsController::class, 'removeRule'])->name('rules.remove');
    Route::post('/bloqueios', [SettingsController::class, 'addBlock'])->name('blocks.add');
    Route::delete('/bloqueios/{block}', [SettingsController::class, 'removeBlock'])->name('blocks.remove');
});
