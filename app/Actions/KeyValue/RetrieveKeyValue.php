<?php

namespace App\Actions\KeyValue;

use App\Models\KeyValueItem;

class RetrieveKeyValue
{
    public function handle(string $key): ?KeyValueItem
    {
        $item = KeyValueItem::query()->where('key', $key)->first();

        if ($item === null) {
            return null;
        }

        if ($item->isExpired()) {
            $item->delete();

            return null;
        }

        return $item;
    }
}