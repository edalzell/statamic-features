<?php

namespace Edalzell\StatamicFeatures\Routes;

use Edalzell\Features\Features;
use Illuminate\Support\Facades\Route;
use Statamic\Statamic;

class Actions
{
    public function __invoke(string $path, Features $features): void
    {
        Statamic::pushActionRoutes(fn () => Route::prefix($features->slug())->group($path));
    }
}
