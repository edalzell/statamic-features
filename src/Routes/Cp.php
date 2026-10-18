<?php

namespace Edalzell\StatamicFeatures\Routes;

use Statamic\Statamic;

class Cp
{
    public function __invoke(string $path): void
    {
        Statamic::pushCpRoutes(fn () => require $path);
    }
}
