<?php

namespace App\Http\Controllers\Admin;

use App\Board\BoardMoves;
use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Inquiry;
use App\Models\Service;
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
        $open = Inquiry::query()->with('service')->withCount('notes')->whereNull('closed_at')
            ->orderByRaw("priority = 'alta' desc")->orderBy('created_at')->get()->groupBy('stage');

        return view('admin.board', [
            'newInquiries' => $open[Inquiry::STAGE_NEW] ?? collect(),
            'waitingInquiries' => $open[Inquiry::STAGE_WAITING] ?? collect(),
            'requested' => Appointment::query()->with($with)->where('status', AppointmentStatus::Requested)->orderBy('starts_at')->get(),
            'confirmed' => Appointment::query()->with($with)->where('status', AppointmentStatus::Confirmed)->orderBy('starts_at')->get(),
            'done' => Appointment::query()->with($with)->where('status', AppointmentStatus::Done)->where('starts_at', '>=', $recent)->orderByDesc('starts_at')->get(),
            'noShow' => Appointment::query()->with($with)->where('status', AppointmentStatus::NoShow)->where('starts_at', '>=', $recent)->orderByDesc('starts_at')->get(),
            'now' => $now,
            'services' => Service::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeInquiry(Request $request): RedirectResponse
    {
        $inquiry = Inquiry::create($this->validated($request));
        $inquiry->log('Cartão criado em "'.Inquiry::STAGES[$inquiry->stage].'"');

        return back()->with('ok', 'Cartão adicionado ao quadro.');
    }

    /** O conteúdo da janela do cartão, carregado ao abrir para editar. */
    public function editInquiry(Inquiry $inquiry): View
    {
        return view('partials.card-modal', [
            'inquiry' => $inquiry->load(['notes.user', 'events']),
            'services' => Service::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function updateInquiry(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $this->validated($request);
        $before = $inquiry->stage;
        $inquiry->update($data);
        $inquiry->log($before !== $inquiry->stage ? 'Editado e movido para "'.Inquiry::STAGES[$inquiry->stage].'"' : 'Dados do cartão editados');

        return back()->with('ok', 'Cartão atualizado.');
    }

    public function addNote(Request $request, Inquiry $inquiry): View
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']], [], ['body' => 'anotação']);
        $inquiry->notes()->create(['body' => $data['body'], 'user_id' => $request->user()->id]);

        return $this->editInquiry($inquiry)->with('tab', 'notas');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return array_filter($this->rules($request), fn ($v) => $v !== null) + ['stage' => Inquiry::STAGE_NEW, 'priority' => 'normal', 'service_id' => null, 'preferred_period' => null, 'patient_name' => null, 'phone' => null, 'note' => null];
    }

    /** @return array<string, mixed> */
    private function rules(Request $request): array
    {
        return $request->validate([
            'guardian_name' => ['required', 'string', 'max:120'],
            'patient_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'channel' => ['required', 'in:'.implode(',', array_keys(Inquiry::CHANNELS))],
            'stage' => ['nullable', 'in:'.implode(',', array_keys(Inquiry::STAGES))],
            'priority' => ['nullable', 'in:'.implode(',', array_keys(Inquiry::PRIORITIES))],
            'service_id' => ['nullable', 'exists:services,id'],
            'preferred_period' => ['nullable', 'in:'.implode(',', array_keys(Inquiry::PERIODS))],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], ['guardian_name' => 'nome']);
    }

    public function moveInquiry(Inquiry $inquiry, string $stage): RedirectResponse
    {
        abort_unless(in_array($stage, [Inquiry::STAGE_NEW, Inquiry::STAGE_WAITING], true), 404);
        $inquiry->update(['stage' => $stage]);
        $inquiry->log('Movido para "'.Inquiry::STAGES[$stage].'"');

        return back();
    }

    public function closeInquiry(Inquiry $inquiry): RedirectResponse
    {
        $inquiry->update(['closed_at' => now()]);
        $inquiry->log('Arquivado');

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
            $result = $moves->move($card, $data['to']);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // O cartão volta redesenhado, já com o selo e os botões da nova coluna.
        if (! isset($result['redirect'])) {
            $result['html'] = $card instanceof Inquiry
                ? view('partials.inquiry', ['inquiry' => $card->fresh()->load('service')->loadCount('notes')])->render()
                : view('partials.kcard', ['appointment' => $card->fresh(['patient', 'service', 'patientPackage.package']), 'now' => CarbonImmutable::now()])->render();
        }

        return response()->json($result);
    }
}
