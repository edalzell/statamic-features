<?php

namespace Edalzell\StatamicFeatures\Tests\Fixtures\Bare\Subscribers;

use Illuminate\Events\Dispatcher;

class BareSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen('bare.subscribed', fn () => null);
    }
}
