<?php

namespace App\Actions\KeyValue;

use App\Models\KeyValueItem;

class StoreKeyValue
{
    public function handle(string $key, string $value, ?int $ttlSeconds = null): KeyValueItem
    {
        return KeyValueItem::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'expires_at' => $ttlSeconds !== null ? now()->addSeconds($ttlSeconds) : null,
            ],
        );
    }
}