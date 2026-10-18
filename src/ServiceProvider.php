<?php

namespace Edalzell\StatamicFeatures;

use Edalzell\Features\Contracts\Extension;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        $this->app->tag(StatamicAddons::class, Extension::TAG);
    }
}
