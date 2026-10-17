<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inquiry extends Model
{
    public const STAGE_NEW = 'new';          // quer agendar, ela ainda não respondeu com horário

    public const STAGE_WAITING = 'waiting';  // ela respondeu; falta o paciente decidir

    public const CHANNELS = ['whatsapp' => 'WhatsApp', 'instagram' => 'Instagram', 'indicacao' => 'Indicação', 'outro' => 'Outro'];

    public const STAGES = [self::STAGE_NEW => 'Quer agendar', self::STAGE_WAITING => 'Aguardando paciente'];

    public const PRIORITIES = ['normal' => 'Normal', 'alta' => 'Prioridade'];

    public const PERIODS = ['manha' => 'Manhã', 'tarde' => 'Tarde', 'qualquer' => 'Qualquer horário'];

    protected $fillable = ['guardian_name', 'patient_name', 'phone', 'channel', 'note', 'stage', 'priority', 'service_id', 'preferred_period', 'closed_at'];

    protected function casts(): array
    {
        return ['closed_at' => 'datetime'];
    }

    public function whatsappNumber(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);
        if ($digits === '') {
            return null;
        }

        return strlen($digits) <= 11 ? '55'.$digits : $digits;
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(InquiryNote::class)->latest('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(InquiryEvent::class)->latest('id');
    }

    /** Registra um passo no histórico do cartão. */
    public function log(string $description): void
    {
        $this->events()->create(['description' => $description]);
    }
}
