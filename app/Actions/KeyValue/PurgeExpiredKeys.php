<?php

namespace App\Actions\KeyValue;

use App\Models\KeyValueItem;

class PurgeExpiredKeys
{
    public function handle(): int
    {
        return KeyValueItem::query()->expired()->delete();
    }
}