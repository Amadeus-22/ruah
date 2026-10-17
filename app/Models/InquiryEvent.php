<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InquiryEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['inquiry_id', 'description'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
