<?php

namespace Edalzell\StatamicFeatures;

use Edalzell\Features\Contracts\Extension;
use Edalzell\StatamicFeatures\Routes\Actions;
use Edalzell\StatamicFeatures\Routes\Cp;
use Edalzell\StatamicFeatures\Routes\Web;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Statamic mounts an addon's web, control panel and action routes itself,
     * and `Route::statamic()` only exists once it has booted, so every feature's
     * go through the same door rather than into a plain route group.
     */
    public function register(): void
    {
        $this->app->tag(Extensions::class, Extension::TAG);

        config()->set('features.route_groups', [
            ...config('features.route_groups', []),
            'actions' => Actions::class,
            'cp' => Cp::class,
            'web' => Web::class,
        ]);
    }
}
