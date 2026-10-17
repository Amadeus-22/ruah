<?php

namespace App\Board;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Inquiry;
use App\Scheduling\Booking;
use DomainException;

/**
 * O que acontece quando um cartão é solto em outra coluna do quadro.
 * A mesma regra decide quais colunas acendem durante o arraste e o que o servidor aceita.
 */
class BoardMoves
{
    public const COLUMNS = ['novo', 'aguardando', 'agendado', 'realizado', 'faltou'];

    public function __construct(private Booking $booking) {}

    /** @return list<string> as colunas onde o cartão pode ser solto */
    public static function allowed(Inquiry|Appointment $card): array
    {
        if ($card instanceof Inquiry) {
            return $card->stage === Inquiry::STAGE_NEW ? ['aguardando', 'agendado'] : ['novo', 'agendado'];
        }

        return match ($card->status) {
            AppointmentStatus::Requested => ['agendado'],
            AppointmentStatus::Confirmed => $card->starts_at->isPast() ? ['realizado', 'faltou'] : [],
            default => [],
        };
    }

    /**
     * @return array{message: string, redirect?: string}
     *
     * @throws DomainException quando o movimento não faz sentido
     */
    public function move(Inquiry|Appointment $card, string $to): array
    {
        if (! in_array($to, self::allowed($card), true)) {
            throw new DomainException($this->refusal($card, $to));
        }

        if ($card instanceof Inquiry) {
            if ($to === 'agendado') {
                // Agendar exige escolher dia e serviço: abre o formulário já preenchido.
                return ['message' => 'Escolha o horário para agendar.', 'redirect' => route('admin.appointments.create', ['inquiry_id' => $card->id])];
            }
            $card->update(['stage' => $to === 'novo' ? Inquiry::STAGE_NEW : Inquiry::STAGE_WAITING]);

            return ['message' => $to === 'novo' ? 'Contato de volta para "Quer agendar".' : 'Contato aguardando o paciente.'];
        }

        match ($to) {
            'agendado' => $this->booking->confirm($card),
            'realizado' => $this->booking->complete($card),
            'faltou' => $this->booking->noShow($card),
        };

        return ['message' => match ($to) {
            'agendado' => 'Pedido confirmado.',
            'realizado' => 'Atendimento marcado como realizado.',
            'faltou' => 'Falta registrada.',
        }];
    }

    private function refusal(Inquiry|Appointment $card, string $to): string
    {
        if ($card instanceof Inquiry) {
            return 'Um contato só vira realizado ou falta depois de agendado.';
        }

        return match ($card->status) {
            AppointmentStatus::Requested => 'Confirme o pedido arrastando para "Agendado".',
            AppointmentStatus::Confirmed => $card->starts_at->isPast()
                ? 'Para desmarcar um horário confirmado, use o botão Cancelar.'
                : 'Esse atendimento ainda não aconteceu.',
            default => 'Esse atendimento já foi encerrado.',
        };
    }
}
