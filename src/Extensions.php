<?php

namespace Edalzell\StatamicFeatures;

use Edalzell\Features\Extension;
use Edalzell\Features\Features;
use Illuminate\Console\Command;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use RuntimeException;
use Statamic\Actions\Action;
use Statamic\Addons\Addon;
use Statamic\Addons\Manifest;
use Statamic\Dictionaries\Dictionary;
use Statamic\Facades\Addon as Addons;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Fieldset;
use Statamic\Fields\Fieldtype;
use Statamic\Modifiers\Modifier;
use Statamic\Query\Scopes\Scope;
use Statamic\Statamic;
use Statamic\Tags\Tags;
use Statamic\UpdateScripts\UpdateScript;
use Statamic\Widgets\Widget;

class Extensions extends Extension
{
    /**
     * The folders Statamic scans in an app or addon, relative to `src`.
     *
     * @var array<string, class-string>
     */
    private const array EXTENSIONS = [
        'Actions' => Action::class,
        'Dictionaries' => Dictionary::class,
        'Fieldtypes' => Fieldtype::class,
        'Modifiers' => Modifier::class,
        'Query/Scopes' => Scope::class,
        'Query/Scopes/Filters' => Scope::class,
        'Scopes' => Scope::class,
        'Tags' => Tags::class,
        'Widgets' => Widget::class,
    ];

    /** @var array<string, string> slug => root path */
    private array $booted = [];

    /**
     * Deferred until every provider has booted. Statamic runs its booted callbacks once, while the app boots,
     * so a feature booted later adds its middleware right away instead of leaving a callback for the next app.
     */
    public function boot(Features $features): void
    {
        $this->bootSerializableClasses($features);

        $app = $features->app();

        $app->booted(fn () => $this->bootFeature($features));

        $app instanceof Application && $app->isBooted()
            ? $this->bootMiddlewareGroups($features)
            : Statamic::booted(fn () => $this->bootMiddlewareGroups($features));
    }

    public function bootFeature(Features $features): void
    {
        if ($this->alreadyBooted($features)) {
            return;
        }

        $this
            ->bootBlueprints($features)
            ->bootExtensions($features)
            ->bootFieldsets($features)
            ->bootSubscribers($features)
            ->bootAssets($features)
            ->bootUpdateScripts($features);
    }

    public function routeGroups(): array
    {
        return [
            'actions' => $this->loadActionRoutes(...),
            'cp' => $this->loadCpRoutes(...),
            'web' => $this->loadWebRoutes(...),
        ];
    }

    private function addon(Features $features): ?Addon
    {
        if (is_null($addon = $this->findAddon($features))) {
            // Not discovered yet — just installed, inside `statamic:install`.
            $features->app()->make(Manifest::class)->build();
            $addon = $this->findAddon($features);
        }

        return $addon;
    }

    private function alreadyBooted(Features $features): bool
    {
        $path = $this->booted[$slug = $features->slug()] ?? null;

        throw_if(
            ! is_null($path) && $path !== $features->rootPath(),
            new RuntimeException("Features at [{$path}] and [{$features->rootPath()}] both have the slug [{$slug}]."),
        );

        $this->booted[$slug] ??= $features->rootPath();

        return ! is_null($path);
    }

    private function bootAssets(Features $features): static
    {
        $publishables = $this->publishables($features);
        $scripts = $this->scripts($features);
        $stylesheets = $this->stylesheets($features);
        $vite = $features->option('statamic.vite');

        if (! empty($publishables)) {
            $this->publish($features, array_map(fn (string $to) => $this->publicPath($features, $to), $publishables));
        }

        collect($scripts)->each(fn (string $path) => Statamic::script($features->slug(), $this->publishAsset($features, $path, 'js')));
        collect($stylesheets)->each(fn (string $path) => Statamic::style($features->slug(), $this->publishAsset($features, $path, 'css')));

        if (! is_null($vite)) {
            $this->registerVite($features, $vite);
        }

        if (! empty([...$publishables, ...$scripts, ...$stylesheets]) || ! is_null($vite)) {
            Statamic::afterInstalled(fn (Command $command) => $this->publishAssets($features, $command));
        }

        return $this;
    }

    private function bootBlueprints(Features $features): static
    {
        if (File::isDirectory($path = $features->pathTo('resources/blueprints'))) {
            Blueprint::addNamespace($features->slug(), $path);
        }

        return $this;
    }

    private function bootExtensions(Features $features): static
    {
        collect(self::EXTENSIONS)
            ->flatMap(fn (string $type, string $folder) => $this->classesIn($features, $folder, $type))
            ->each(fn (string $extension) => $extension::register());

        return $this;
    }

    private function bootFieldsets(Features $features): static
    {
        if (File::isDirectory($path = $features->pathTo('resources/fieldsets'))) {
            Fieldset::addNamespace($features->slug(), $path);
        }

        return $this;
    }

    private function bootMiddlewareGroups(Features $features): static
    {
        $router = $features->app()->make(Router::class);

        collect($this->middlewareGroups($features, 'statamic.middleware_groups'))
            ->each(fn (array $middleware, string $group) => collect($middleware)
                ->each(fn (string $class) => $router->pushMiddlewareToGroup($group, $class)));

        // Reversed, so the group ends up in the order they are listed.
        collect($this->middlewareGroups($features, 'statamic.prepend_middleware_groups'))
            ->each(fn (array $middleware, string $group) => collect($middleware)
                ->reverse()
                ->each(fn (string $class) => $router->prependMiddlewareToGroup($group, $class)));

        return $this;
    }

    private function bootSerializableClasses(Features $features): static
    {
        $existing = config('cache.serializable_classes');

        if (is_null($existing) || $existing === true) {
            return $this;
        }

        config([
            'cache.serializable_classes' => array_merge(
                is_array($existing) ? $existing : [],
                $features->option('statamic.serializable_classes', []),
            ),
        ]);

        return $this;
    }

    private function bootSubscribers(Features $features): static
    {
        $this->classesIn($features, 'Subscribers')->each(fn (string $subscriber) => Event::subscribe($subscriber));

        return $this;
    }

    private function bootUpdateScripts(Features $features): static
    {
        if (! File::isDirectory($features->pathTo('src/UpdateScripts'))) {
            return $this;
        }

        if (is_null($addon = $this->addon($features))) {
            return $this;
        }

        $this
            ->classesIn($features, 'UpdateScripts', UpdateScript::class)
            ->each(fn (string $script) => $script::register($addon->package()));

        return $this;
    }

    /**
     * @param  class-string|null  $type
     * @return Collection<int, class-string>
     */
    private function classesIn(Features $features, string $folder, ?string $type = null): Collection
    {
        return $features->classes($folder, $type, depth: 0);
    }

    /**
     * @return Collection<int, string>
     */
    private function cpDirectories(Features $features): Collection
    {
        if (! File::isDirectory($path = $features->pathTo('resources/cp'))) {
            return collect();
        }

        return collect(File::directories($path));
    }

    private function findAddon(Features $features): ?Addon
    {
        return Addons::all()
            ->first(fn (Addon $addon) => str_starts_with($features->rootNamespace(), $addon->namespace().'\\'));
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
     * @return array<string, array<int, class-string>>
     */
    private function middlewareGroups(Features $features, string $option): array
    {
        return $features->option($option, []);
    }

    private function publicPath(Features $features, string $path): string
    {
        return public_path("vendor/{$features->slug()}/{$path}");
    }

    /**
     * @param  array<string, string>  $paths
     */
    private function publish(Features $features, array $paths): void
    {
        (new ReflectionMethod($features->provider(), 'publishes'))->invoke($features->provider(), $paths, $features->slug());
    }

    private function publishAsset(Features $features, string $path, string $folder): string
    {
        throw_unless(
            is_file($path),
            new RuntimeException("Asset [{$path}] of feature [{$features->slug()}] does not exist."),
        );

        $this->publish($features, [$path => $this->publicPath($features, "{$folder}/".basename($path))]);

        return basename($path).'?v='.filemtime($path);
    }

    /**
     * @return array<string, string>
     */
    private function publishables(Features $features): array
    {
        return $features->option('statamic.publishables') ?? $this->cpDirectories($features)
            ->reject(fn (string $directory) => in_array(basename($directory), ['css', 'js']))
            ->mapWithKeys(fn (string $directory) => [$directory => basename($directory)])
            ->all();
    }

    private function publishAssets(Features $features, Command $command): void
    {
        $command->call('vendor:publish', ['--force' => true, '--tag' => $features->slug()]);
    }

    /**
     * @param  array<string, mixed>|string|array<int, string>  $config
     */
    private function registerVite(Features $features, array|string $config): void
    {
        if (is_string($config) || ! Arr::isAssoc($config)) {
            $config = ['input' => $config];
        }

        $publicDirectory = $config['publicDirectory'] ?? 'public';
        $buildDirectory = $config['buildDirectory'] ?? 'build';
        $slug = $features->slug();

        $this->publish($features, [
            $features->pathTo("{$publicDirectory}/{$buildDirectory}").'/' => public_path("vendor/{$slug}/{$buildDirectory}/"),
        ]);

        Statamic::vite($slug, [
            'hotFile' => $config['hotFile'] ?? $features->pathTo("{$publicDirectory}/hot"),
            'buildDirectory' => "vendor/{$slug}/{$buildDirectory}",
            'input' => $config['input'],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function scripts(Features $features): array
    {
        return $features->option('statamic.scripts') ?? File::glob($features->pathTo('resources/cp/js/*.js'));
    }

    /**
     * @return array<int, string>
     */
    private function stylesheets(Features $features): array
    {
        return $features->option('statamic.stylesheets') ?? File::glob($features->pathTo('resources/cp/css/*.css'));
    }
}
