<?php

namespace Edalzell\StatamicFeatures;

use Edalzell\Features\Contracts\Extension;
use Edalzell\Features\ServiceProvider as FeaturesServiceProvider;
use Edalzell\StatamicFeatures\Routes\Actions;
use Edalzell\StatamicFeatures\Routes\Cp;
use Edalzell\StatamicFeatures\Routes\Web;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use ReflectionClass;

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

    /**
     * Runs once every package has registered, so laravel-features' own config
     * is already merged whichever of the two registered first. A group the app
     * has changed from laravel-features' default is left alone.
     */
    public function fillRouteGroups(): void
    {
        $groups = config('features.route_groups', []);
        $defaults = $this->featuresDefaults()['route_groups'];

        $ours = collect(self::ROUTE_GROUPS)
            ->filter(fn (string $loader, string $file) => ($groups[$file] ?? null) === ($defaults[$file] ?? null));

        config()->set('features.route_groups', [...$groups, ...$ours->all()]);
    }

    public function register(): void
    {
        $this->app->singleton(Extensions::class);
        $this->app->tag(Extensions::class, Extension::TAG);
        $this->app->booting($this->fillRouteGroups(...));
    }

    /** @return array{route_groups: array<string, mixed>} */
    private function featuresDefaults(): array
    {
        $root = dirname((string) (new ReflectionClass(FeaturesServiceProvider::class))->getFileName(), 2);

        return require "{$root}/config/features.php";
    }
}
