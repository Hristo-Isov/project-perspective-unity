<?php

namespace App\Actions\KeyValue;

use App\Models\KeyValueItem;

class RetrieveKeyValue
{
    public function handle(string $key): ?KeyValueItem
    {
        KeyValueItem::query()->where('key',$key)->expired()->delete();
        return KeyValueItem::query()->where('key',$key)->first();
    }
}