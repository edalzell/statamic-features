<?php

namespace Edalzell\StatamicFeatures\Routes;

use Illuminate\Support\Facades\Route;
use Statamic\Statamic;

class Web
{
    public function __invoke(string $path): void
    {
        Statamic::pushWebRoutes(fn () => Route::group([], $path));
    }
}
