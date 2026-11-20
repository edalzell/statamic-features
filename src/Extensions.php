<?php

namespace Edalzell\StatamicFeatures;

use Edalzell\Features\Extension;
use Edalzell\Features\Features;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use RuntimeException;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Fieldset;
use Statamic\Statamic;

class Extensions extends Extension
{
    /** @var array<string, string> slug => root path */
    private array $booted = [];

    /**
     * Deferred until every provider has booted. Statamic runs its booted callbacks once, while the app boots,
     * so a feature booted later adds its middleware right away instead of leaving a callback for the next app.
     */
    public function boot(Features $features): void
    {
        $feature = new StatamicFeature($features);

        $this->bootSerializableClasses($feature);

        $feature->app()->booted(fn () => $this->bootFeature($feature));

        Statamic::hasBooted()
            ? $this->bootMiddlewareGroups($feature)
            : Statamic::booted(fn () => $this->bootMiddlewareGroups($feature));
    }

    public function bootFeature(StatamicFeature $feature): void
    {
        if ($this->alreadyBooted($feature)) {
            return;
        }

        $this
            ->bootBlueprints($feature)
            ->bootExtensions($feature)
            ->bootFieldsets($feature)
            ->bootAssets($feature)
            ->bootUpdateScripts($feature);
    }

    public function routeGroups(): array
    {
        return [
            'actions' => $this->loadActionRoutes(...),
            'cp' => $this->loadCpRoutes(...),
            'web' => $this->loadWebRoutes(...),
        ];
    }

    private function alreadyBooted(StatamicFeature $feature): bool
    {
        $path = $this->booted[$slug = $feature->slug()] ?? null;

        throw_if(
            ! is_null($path) && $path !== $feature->rootPath(),
            new RuntimeException("Features at [{$path}] and [{$feature->rootPath()}] both have the slug [{$slug}]."),
        );

        $this->booted[$slug] ??= $feature->rootPath();

        return ! is_null($path);
    }

    private function bootAssets(StatamicFeature $feature): static
    {
        $publishables = $feature->publishables();
        $scripts = $feature->scripts();
        $stylesheets = $feature->stylesheets();
        $vite = $feature->vite();

        if (! empty($publishables)) {
            $this->publish($feature, array_map(fn (string $to) => $feature->publicPath($to), $publishables));
        }

        collect($scripts)->each(fn (string $path) => Statamic::script($feature->slug(), $this->publishAsset($feature, $path, 'js')));
        collect($stylesheets)->each(fn (string $path) => Statamic::style($feature->slug(), $this->publishAsset($feature, $path, 'css')));

        if (! is_null($vite)) {
            $this->registerVite($feature, $vite);
        }

        if (! empty([...$publishables, ...$scripts, ...$stylesheets]) || ! is_null($vite)) {
            Statamic::afterInstalled(fn (Command $command) => $this->publishAssets($feature, $command));
        }

        return $this;
    }

    private function bootBlueprints(StatamicFeature $feature): static
    {
        if (File::isDirectory($path = $feature->pathTo('resources/blueprints'))) {
            Blueprint::addNamespace($feature->slug(), $path);
        }

        return $this;
    }

    private function bootExtensions(StatamicFeature $feature): static
    {
        $feature->extensions()->each(fn (string $extension) => $extension::register());

        return $this;
    }

    private function bootFieldsets(StatamicFeature $feature): static
    {
        if (File::isDirectory($path = $feature->pathTo('resources/fieldsets'))) {
            Fieldset::addNamespace($feature->slug(), $path);
        }

        return $this;
    }

    private function bootMiddlewareGroups(StatamicFeature $feature): static
    {
        $router = $feature->app()->make(Router::class);

        collect($feature->middlewareGroups('statamic.middleware_groups'))
            ->each(fn (array $middleware, string $group) => collect($middleware)
                ->each(fn (string $class) => $router->pushMiddlewareToGroup($group, $class)));

        // Reversed, so the group ends up in the order they are listed.
        collect($feature->middlewareGroups('statamic.prepend_middleware_groups'))
            ->each(fn (array $middleware, string $group) => collect($middleware)
                ->reverse()
                ->each(fn (string $class) => $router->prependMiddlewareToGroup($group, $class)));

        return $this;
    }

    private function bootSerializableClasses(StatamicFeature $feature): static
    {
        $existing = config('cache.serializable_classes');

        if (is_null($existing) || $existing === true) {
            return $this;
        }

        config([
            'cache.serializable_classes' => array_merge(
                is_array($existing) ? $existing : [],
                $feature->serializableClasses(),
            ),
        ]);

        return $this;
    }

    private function bootUpdateScripts(StatamicFeature $feature): static
    {
        if (! $feature->hasUpdateScripts()) {
            return $this;
        }

        if (is_null($addon = $feature->addon())) {
            return $this;
        }

        $feature->updateScripts()->each(fn (string $script) => $script::register($addon->package()));

        return $this;
    }

    private function loadActionRoutes(string $path, Features $features): void
    {
        Statamic::pushActionRoutes(fn () => Route::prefix($features->slug())->group($path));
    }

    private function loadCpRoutes(string $path): void
    {
        Statamic::pushCpRoutes(fn () => Route::group([], $path));
    }

    private function loadWebRoutes(string $path): void
    {
        Statamic::pushWebRoutes(fn () => Route::group([], $path));
    }

    /**
     * @param  array<string, string>  $paths
     */
    private function publish(StatamicFeature $feature, array $paths): void
    {
        (new ReflectionMethod($feature->provider(), 'publishes'))->invoke($feature->provider(), $paths, $feature->slug());
    }

    private function publishAsset(StatamicFeature $feature, string $path, string $folder): string
    {
        throw_unless(
            is_file($path),
            new RuntimeException("Asset [{$path}] of feature [{$feature->slug()}] does not exist."),
        );

        $this->publish($feature, [$path => $feature->publicPath("{$folder}/".basename($path))]);

        return basename($path).'?v='.filemtime($path);
    }

    private function publishAssets(StatamicFeature $feature, Command $command): void
    {
        $command->call('vendor:publish', ['--force' => true, '--tag' => $feature->slug()]);
    }

    /**
     * @param  array<string, mixed>|string|array<int, string>  $config
     */
    private function registerVite(StatamicFeature $feature, array|string $config): void
    {
        if (is_string($config) || ! Arr::isAssoc($config)) {
            $config = ['input' => $config];
        }

        $publicDirectory = $config['publicDirectory'] ?? 'public';
        $buildDirectory = $config['buildDirectory'] ?? 'build';
        $slug = $feature->slug();

        $this->publish($feature, [
            $feature->pathTo("{$publicDirectory}/{$buildDirectory}").'/' => public_path("vendor/{$slug}/{$buildDirectory}/"),
        ]);

        Statamic::vite($slug, [
            'hotFile' => $config['hotFile'] ?? $feature->pathTo("{$publicDirectory}/hot"),
            'buildDirectory' => "vendor/{$slug}/{$buildDirectory}",
            'input' => $config['input'],
        ]);
    }
}
