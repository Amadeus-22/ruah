<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'clinic_name' => 'Consultório de Fisioterapia',
        'clinic_address' => '',
        'slot_step_minutes' => '30',       // de quanto em quanto tempo um horário pode começar
        'buffer_minutes' => '10',          // intervalo entre um paciente e outro
        'min_notice_hours' => '12',        // antecedência mínima para pedir um horário
        'max_days_ahead' => '30',          // até quantos dias à frente a página mostra
        'show_prices' => '1',
        'no_show_uses_session' => '1',     // falta desconta sessão do pacote?
    ];

    public static function get(string $key): string
    {
        return static::query()->find($key)?->value ?? (self::DEFAULTS[$key] ?? '');
    }

    public static function int(string $key): int
    {
        return (int) static::get($key);
    }

    public static function bool(string $key): bool
    {
        return static::get($key) === '1';
    }

    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value ?? '']);
    }
}
