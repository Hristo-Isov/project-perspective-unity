<?php

namespace App\Actions\Stack;

use App\Models\StackItem;
use Illuminate\Support\Facades\DB;

class PopFromStack
{
    public function handle(): ?StackItem
    {
        return DB::transaction(function () {
            $item = StackItem::query()
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $item?->delete();

            return $item;
        });
    }
}
