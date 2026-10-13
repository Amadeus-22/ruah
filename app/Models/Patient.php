<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = ['guardian_name', 'patient_name', 'phone', 'birth_date', 'notes'];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(PatientPackage::class);
    }

    /** Só dígitos, com DDI do Brasil quando falta: é o formato do link do WhatsApp. */
    public function whatsappNumber(): string
    {
        $digits = preg_replace('/\D+/', '', $this->phone);

        return strlen($digits) <= 11 ? '55'.$digits : $digits;
    }
}
