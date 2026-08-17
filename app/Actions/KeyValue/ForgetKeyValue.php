<?php

namespace App\Actions\KeyValue;

use App\Models\KeyValueItem;

class ForgetKeyValue
{
    public function handle(string $key): bool
    {
        return KeyValueItem::query()->where('key', $key)->delete() > 0;
    }
}