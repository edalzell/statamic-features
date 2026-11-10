<?php

namespace Edalzell\StatamicFeatures;

use Edalzell\Features\Extension;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Statamic runs its booted callbacks once, after `app()->isBooted()` is already true. Until it says so
     * itself (https://github.com/statamic/cms/pull/15823), this binding records that it has.
     */
    public function boot(): void
    {
        Statamic::booted(fn () => $this->app->instance('statamic.booted', true));
    }

    public function register(): void
    {
        $this->app->singleton(Extensions::class);
        $this->app->tag(Extensions::class, Extension::TAG);
    }
}
