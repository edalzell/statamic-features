<?php

namespace Edalzell\StatamicFeatures;

use Edalzell\Features\Extension;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Extensions::class);
        $this->app->tag(Extensions::class, Extension::TAG);
    }
}
