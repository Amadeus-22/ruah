<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'service_id', 'patient_package_id', 'starts_at', 'ends_at', 'status',
        'price_cents', 'paid_at', 'request_note', 'public_token', 'calendar_event_id',
        'payment_method', 'invoice_number', 'invoiced_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'paid_at' => 'datetime',
            'invoiced_at' => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function patientPackage(): BelongsTo
    {
        return $this->belongsTo(PatientPackage::class);
    }
}
