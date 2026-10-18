<?php

namespace Edalzell\StatamicFeatures\Providers;

use Closure;
use Edalzell\Features\Providers\FeatureServiceProvider as BaseProvider;
use Illuminate\Support\Facades\Route;
use Statamic\Statamic;

/**
 * Optional. Any feature gets its Statamic pieces registered through the tagged
 * extension; extending this instead adds the addon-style asset properties and
 * sends the feature's web, control panel and action routes through Statamic.
 */
abstract class FeatureServiceProvider extends BaseProvider
{
    /** @var array<string, string> */
    protected array $publishables = [];

    /** @var array<int, string> */
    protected array $scripts = [];

    /** @var array<int, string> */
    protected array $stylesheets = [];

    /** @return array<string, mixed> */
    protected function options(): array
    {
        return [
            'statamic.publishables' => $this->publishables,
            'statamic.scripts' => $this->scripts,
            'statamic.stylesheets' => $this->stylesheets,
        ];
    }

    /**
     * Statamic mounts an addon's web, control panel and action routes itself, so a
     * feature's go through the same door rather than into a plain route group.
     *
     * @return array<string, array<string, mixed>|Closure>
     */
    protected function routeGroups(): array
    {
        return [
            ...parent::routeGroups(),
            'actions' => fn (string $path) => Statamic::pushActionRoutes(fn () => $this->actionRoutes($path)),
            'cp' => fn (string $path) => Statamic::pushCpRoutes(fn () => require $path),
            'web' => fn (string $path) => Statamic::pushWebRoutes(fn () => require $path),
        ];
    }

    private function actionRoutes(string $path): void
    {
        Route::prefix($this->slug())->group($path);
    }
}
