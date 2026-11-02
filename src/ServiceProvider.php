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
     * and `Route::statamic()` only exists once it has booted, so a feature's go
     * through the same door rather than into a plain route group.
     *
     * @var array<string, class-string>
     */
    private const array ROUTE_GROUPS = [
        'actions' => Actions::class,
        'cp' => Cp::class,
        'web' => Web::class,
    ];

    public function register(): void
    {
        $this->app->singleton(Extensions::class);
        $this->app->tag(Extensions::class, Extension::TAG);

        // Once every package has registered, so nothing merges config over ours.
        $this->app->booting(fn () => config()->set('features.route_groups', [...config('features.route_groups', []), ...self::ROUTE_GROUPS]));
    }
}
