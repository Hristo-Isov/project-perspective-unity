<?php

namespace App\Console\Commands;

use App\Models\KeyValueItem;
use Illuminate\Console\Command;

class PruneExpiredKeys extends Command
{
    protected $signature = 'kv:prune-expired';

    protected $description = 'Delete key-value entries whose TTL has passed';

    public function handle(): int
    {
        $deleted = KeyValueItem::purgeExpired();

        $this->info("Pruned {$deleted} expired key(s).");

        return self::SUCCESS;
    }
}
