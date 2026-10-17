<?php

use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BoardController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\CalendarFeedController;
use App\Http\Controllers\PublicBookingController;
use Illuminate\Support\Facades\Route;

// Página pública: o link que vai na bio do Instagram e no WhatsApp.
Route::get('/', [PublicBookingController::class, 'index'])->name('public.index');
Route::get('/agendar/{service}', [PublicBookingController::class, 'slots'])->name('public.slots');
Route::get('/agendar/{service}/dados', [PublicBookingController::class, 'form'])->name('public.form');
Route::post('/agendar/{service}', [PublicBookingController::class, 'store'])->middleware('throttle:10,1')->name('public.store');
Route::get('/pedido/{token}', [PublicBookingController::class, 'show'])->name('public.show');

Route::view('/privacidade', 'public.privacy')->name('public.privacy');

// Agenda para o celular assinar; o código no link é o que dá acesso.
Route::get('/agenda/{token}.ics', CalendarFeedController::class)->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:60,1')->name('calendar.feed');

Route::get('/entrar', [AuthController::class, 'form'])->name('login');
Route::post('/entrar', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/sair', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->prefix('painel')->name('admin.')->group(function () {
    Route::get('/', [BoardController::class, 'index'])->name('dashboard');
    Route::get('/agenda', DashboardController::class)->name('agenda');
    Route::post('/quadro/mover', [BoardController::class, 'move'])->name('board.move');
    Route::post('/contatos', [BoardController::class, 'storeInquiry'])->name('inquiries.store');
    Route::post('/contatos/{inquiry}/etapa/{stage}', [BoardController::class, 'moveInquiry'])->name('inquiries.move');
    Route::post('/contatos/{inquiry}/arquivar', [BoardController::class, 'closeInquiry'])->name('inquiries.close');

    Route::get('/agendamentos/novo', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/agendamentos', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::post('/agendamentos/{appointment}/lembrete', [AppointmentController::class, 'remind'])->name('appointments.remind');
    Route::post('/agendamentos/{appointment}/{action}', [AppointmentController::class, 'action'])->name('appointments.action');
    Route::put('/agendamentos/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');

    Route::get('/pacientes', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/pacientes/{patient}', [PatientController::class, 'show'])->name('patients.show');
    Route::put('/pacientes/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::post('/pacientes/{patient}/pacotes', [PatientController::class, 'sellPackage'])->name('patients.packages');

    Route::get('/servicos', [CatalogController::class, 'index'])->name('catalog');
    Route::post('/servicos/{service?}', [CatalogController::class, 'saveService'])->name('services.save');
    Route::post('/pacotes/{package?}', [CatalogController::class, 'savePackage'])->name('packages.save');

    Route::get('/visao', OverviewController::class)->name('overview');

    Route::get('/perfil', [ProfileController::class, 'show'])->name('profile');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/perfil/foto', [ProfileController::class, 'photo'])->name('profile.photo');

    Route::get('/financeiro', [FinanceController::class, 'index'])->name('finance');
    Route::get('/financeiro/exportar', [FinanceController::class, 'export'])->name('finance.export');
    Route::put('/financeiro/{kind}/{id}', [FinanceController::class, 'update'])->name('finance.update');

    Route::get('/configuracoes', [SettingsController::class, 'index'])->name('settings');
    Route::post('/configuracoes', [SettingsController::class, 'save'])->name('settings.save');
    Route::post('/configuracoes/agenda-celular', [SettingsController::class, 'newFeedLink'])->name('settings.feed');
    Route::post('/horarios', [SettingsController::class, 'addRule'])->name('rules.add');
    Route::delete('/horarios/{rule}', [SettingsController::class, 'removeRule'])->name('rules.remove');
    Route::post('/bloqueios', [SettingsController::class, 'addBlock'])->name('blocks.add');
    Route::delete('/bloqueios/{block}', [SettingsController::class, 'removeBlock'])->name('blocks.remove');
});
