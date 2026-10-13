<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientPackage extends Model
{
    use HasFactory;

    protected $fillable = ['patient_id', 'package_id', 'sessions_total', 'sessions_used', 'price_cents', 'purchased_on', 'paid_at', 'payment_method', 'invoice_number', 'invoiced_at'];

    protected function casts(): array
    {
        return ['purchased_on' => 'date', 'paid_at' => 'datetime', 'invoiced_at' => 'datetime'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function remaining(): int
    {
        return max($this->sessions_total - $this->sessions_used, 0);
    }
}
