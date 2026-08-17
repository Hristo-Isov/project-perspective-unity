<?php

namespace App\Actions\KeyValue;

use App\Models\KeyValueItem;

class PurgeExpiredKeys
{
    public function handle(): int
    {
        return KeyValueItem::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();
    }
}