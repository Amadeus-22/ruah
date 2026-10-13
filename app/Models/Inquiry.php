<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    public const STAGE_NEW = 'new';          // quer agendar, ela ainda não respondeu com horário

    public const STAGE_WAITING = 'waiting';  // ela respondeu; falta o paciente decidir

    public const CHANNELS = ['whatsapp' => 'WhatsApp', 'instagram' => 'Instagram', 'outro' => 'Outro'];

    protected $fillable = ['guardian_name', 'patient_name', 'phone', 'channel', 'note', 'stage', 'closed_at'];

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
}
