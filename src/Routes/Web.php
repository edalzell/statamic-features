<?php

namespace Edalzell\StatamicFeatures\Routes;

use Statamic\Statamic;

class Web
{
    public function __invoke(string $path): void
    {
        Statamic::pushWebRoutes(fn () => require $path);
    }
}
