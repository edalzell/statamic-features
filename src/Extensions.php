<?php

namespace Edalzell\StatamicFeatures;

use Edalzell\Features\Contracts\Extension;
use Edalzell\Features\Features;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
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

/**
 * What Statamic's AddonServiceProvider does for an addon, done for a feature.
 * laravel-features hands every feature here once its Laravel pieces have booted;
 * Statamic's own registries only know the app's and each addon's, so without
 * this a feature's tags, fieldtypes and the rest go unseen.
 */
class Extensions implements Extension
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
     * Deferred until every provider has booted, Statamic's included. Not
     * `Statamic::booted()`: that queue drains once, during Statamic's own boot,
     * so a feature booting after it would never run.
     */
    public function boot(Features $features): void
    {
        $features->app()->booted(fn () => $this->bootFeature($features));
    }

    /**
     * Everything registered here is keyed by the feature's slug — the publish
     * tag, the asset bundle, the blueprint and fieldset namespaces — so two
     * features with one slug would silently share them. A feature booting twice
     * is skipped: Statamic's registries append rather than replace.
     */
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

    /**
     * Control panel assets: `resources/cp/js` and `resources/cp/css` by
     * convention, any other `resources/cp` directory published as-is, or
     * whatever the feature names in its `statamic.*` options. All tagged with
     * the slug for `vendor:publish`, and published after `statamic:install`,
     * which only publishes an addon's by the addon's tag.
     */
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

    private function bootSubscribers(Features $features): static
    {
        $this->classesIn($features, 'Subscribers')->each(fn (string $subscriber) => Event::subscribe($subscriber));

        return $this;
    }

    /**
     * An update script runs when a composer package changes version, so only a
     * feature inside an addon has anything to run against.
     */
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
     * One level deep, as Statamic scans an addon; nested folders are the
     * feature's own business and are never loaded.
     *
     * @param  class-string|null  $type
     * @return Collection<int, class-string>
     */
    private function classesIn(Features $features, string $folder, ?string $type = null): Collection
    {
        return $features->classes($folder, $type, depth: 0);
    }

    /** @return Collection<int, string> */
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

    private function publicPath(Features $features, string $path): string
    {
        return public_path("vendor/{$features->slug()}/{$path}");
    }

    /** @param  array<string, string>  $paths */
    private function publish(Features $features, array $paths): void
    {
        (new ReflectionMethod($features->provider(), 'publishes'))->invoke($features->provider(), $paths, $features->slug());
    }

    /**
     * Versioned by the file's modification time rather than a package version,
     * which a feature in a site doesn't have.
     */
    private function publishAsset(Features $features, string $path, string $folder): string
    {
        throw_unless(
            is_file($path),
            new RuntimeException("Asset [{$path}] of feature [{$features->slug()}] does not exist."),
        );

        $this->publish($features, [$path => $this->publicPath($features, "{$folder}/".basename($path))]);

        return basename($path).'?v='.filemtime($path);
    }

    /** @return array<string, string> */
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
     * As Statamic registers an addon's Vite build, with the feature's slug
     * where the addon's package name would go.
     *
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

    /** @return array<int, string> */
    private function scripts(Features $features): array
    {
        return $features->option('statamic.scripts') ?? File::glob($features->pathTo('resources/cp/js/*.js'));
    }

    /** @return array<int, string> */
    private function stylesheets(Features $features): array
    {
        return $features->option('statamic.stylesheets') ?? File::glob($features->pathTo('resources/cp/css/*.css'));
    }
}
