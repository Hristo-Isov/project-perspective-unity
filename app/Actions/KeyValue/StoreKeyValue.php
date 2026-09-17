<?php

namespace App\Actions\KeyValue;

use App\Models\KeyValueItem;

class StoreKeyValue
{
    public function handle(array $data): KeyValueItem
    {
        $ttl = $data['ttl'] ?? null;

        return KeyValueItem::updateOrCreate(
            ['key' => $data['key']],
            [
                'value' => $data['value'],
                'expires_at' => $ttl !== null ? now()->addSeconds((int)$ttl) : null, 
            ],
        );
    }
}