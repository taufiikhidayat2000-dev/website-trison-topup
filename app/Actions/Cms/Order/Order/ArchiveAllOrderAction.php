<?php

namespace App\Actions\Cms\Order\Order;

use App\Models\Order\Order;

class ArchiveAllOrderAction
{
    public function handle(string|array $provider): void
    {
        $providers = (array) $provider;

        Order::whereHas('product', fn ($q) => $q->whereIn('provider', $providers))
            ->withoutArchive()
            ->update([
                'archive_at' => now(),
            ]);
    }
}
