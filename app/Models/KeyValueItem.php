<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeyValueItem extends Model
{
    protected $fillable = ['key', 'value', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

}