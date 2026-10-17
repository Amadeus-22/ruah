<?php

namespace App\Http\Controllers\Admin;

use App\Board\BoardMoves;
use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Inquiry;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** O quadro de triagem: do primeiro contato ao atendimento realizado. */
class BoardController extends Controller
{
    public function index(): View
    {
        $with = ['patient', 'service', 'patientPackage.package'];
        $now = CarbonImmutable::now();
        $recent = $now->subDays(7);
        $open = Inquiry::query()->whereNull('closed_at')->orderBy('created_at')->get()->groupBy('stage');

        return view('admin.board', [
            'newInquiries' => $open[Inquiry::STAGE_NEW] ?? collect(),
            'waitingInquiries' => $open[Inquiry::STAGE_WAITING] ?? collect(),
            'requested' => Appointment::query()->with($with)->where('status', AppointmentStatus::Requested)->orderBy('starts_at')->get(),
            'confirmed' => Appointment::query()->with($with)->where('status', AppointmentStatus::Confirmed)->orderBy('starts_at')->get(),
            'done' => Appointment::query()->with($with)->where('status', AppointmentStatus::Done)->where('starts_at', '>=', $recent)->orderByDesc('starts_at')->get(),
            'noShow' => Appointment::query()->with($with)->where('status', AppointmentStatus::NoShow)->where('starts_at', '>=', $recent)->orderByDesc('starts_at')->get(),
            'now' => $now,
        ]);
    }

    public function storeInquiry(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'guardian_name' => ['required', 'string', 'max:120'],
            'patient_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'channel' => ['required', 'in:'.implode(',', array_keys(Inquiry::CHANNELS))],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], ['guardian_name' => 'nome']);

        Inquiry::create($data + ['stage' => Inquiry::STAGE_NEW]);

        return back()->with('ok', 'Contato adicionado ao quadro.');
    }

    public function moveInquiry(Inquiry $inquiry, string $stage): RedirectResponse
    {
        abort_unless(in_array($stage, [Inquiry::STAGE_NEW, Inquiry::STAGE_WAITING], true), 404);
        $inquiry->update(['stage' => $stage]);

        return back();
    }

    public function closeInquiry(Inquiry $inquiry): RedirectResponse
    {
        $inquiry->update(['closed_at' => now()]);

        return back()->with('ok', 'Contato arquivado.');
    }

    /** Arrastar e soltar: move o cartão e responde em JSON para a tela. */
    public function move(Request $request, BoardMoves $moves): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', 'in:contato,agendamento'],
            'id' => ['required', 'integer'],
            'to' => ['required', 'in:'.implode(',', BoardMoves::COLUMNS)],
        ]);

        $card = $data['kind'] === 'contato'
            ? Inquiry::query()->whereNull('closed_at')->findOrFail($data['id'])
            : Appointment::query()->findOrFail($data['id']);

        try {
            return response()->json($moves->move($card, $data['to']));
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
