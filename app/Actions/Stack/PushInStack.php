<?php

namespace App\Actions\Stack;

use App\Models\StackItem;

class PushInStack
{
    public function handle(string $value): StackItem
    {
        return StackItem::create(['value' => $value]);
    }
}
