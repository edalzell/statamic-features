<?php

namespace Edalzell\StatamicFeatures\Routes;

use Illuminate\Support\Facades\Route;
use Statamic\Statamic;

class Cp
{
    public function __invoke(string $path): void
    {
        Statamic::pushCpRoutes(fn () => Route::group([], $path));
    }
}
