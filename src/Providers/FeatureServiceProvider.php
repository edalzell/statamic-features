<?php

namespace Edalzell\StatamicFeatures\Providers;

use Closure;
use Edalzell\Features\Providers\FeatureServiceProvider as BaseProvider;
use Edalzell\StatamicFeatures\Features;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Statamic\Statamic;

abstract class FeatureServiceProvider extends BaseProvider
{
    /** @var array<int, string> */
    protected array $scripts = [];

    /** @var array<int, string> */
    protected array $stylesheets = [];

    private Features $statamic;

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->statamic = (new Features($this))
            ->path($this->featuresPath())
            ->scripts($this->scripts)
            ->slug($this->slug())
            ->stylesheets($this->stylesheets);
    }

    public function boot(): void
    {
        parent::boot();

        Statamic::booted(fn () => $this->statamic->bootFeature());
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
