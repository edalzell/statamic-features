<?php

namespace Edalzell\StatamicFeatures;

use Edalzell\Features\Features as LaravelFeature;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Statamic\Actions\Action;
use Statamic\Addons\Addon;
use Statamic\Addons\Manifest;
use Statamic\Dictionaries\Dictionary;
use Statamic\Facades\Addon as Addons;
use Statamic\Fields\Fieldtype;
use Statamic\Modifiers\Modifier;
use Statamic\Query\Scopes\Scope;
use Statamic\Tags\Tags;
use Statamic\UpdateScripts\UpdateScript;
use Statamic\Widgets\Widget;

/**
 * What Statamic wants to know about a feature, over laravel-features' view of it.
 */
final class Feature
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

    public function __construct(private readonly LaravelFeature $laravelFeature) {}

    /**
     * The addon the feature lives in, if any.
     */
    public function addon(): ?Addon
    {
        if (is_null($addon = $this->findAddon())) {
            // Not discovered yet — just installed, inside `statamic:install`.
            $this->app()->make(Manifest::class)->build();
            $addon = $this->findAddon();
        }

        return $addon;
    }

    public function app(): Application
    {
        return $this->laravelFeature->app();
    }

    /**
     * @return Collection<int, class-string>
     */
    public function extensions(): Collection
    {
        return collect(self::EXTENSIONS)->flatMap(fn (string $type, string $folder) => $this->classesIn($folder, $type));
    }

    /**
     * @return array<string, array<int, class-string>>
     */
    public function middlewareGroups(string $option): array
    {
        return $this->option($option, []);
    }

    public function pathTo(string $relative = ''): string
    {
        return $this->laravelFeature->pathTo($relative);
    }

    public function provider(): ServiceProvider
    {
        return $this->laravelFeature->provider();
    }

    public function publicPath(string $path): string
    {
        return public_path("vendor/{$this->slug()}/{$path}");
    }

    /**
     * @return array<string, string>
     */
    public function publishables(): array
    {
        return $this->option('statamic.publishables') ?? $this->cpDirectories()
            ->reject(fn (string $directory) => in_array(basename($directory), ['css', 'js']))
            ->mapWithKeys(fn (string $directory) => [$directory => basename($directory)])
            ->all();
    }

    /**
     * @return array<class-string, array<int, class-string<Scope>>>
     */
    public function queryScopes(): array
    {
        return $this->option('statamic.query_scopes', []);
    }

    public function rootPath(): string
    {
        return $this->laravelFeature->rootPath();
    }

    /**
     * @return array<int, string>
     */
    public function scripts(): array
    {
        return $this->option('statamic.scripts') ?? File::glob($this->pathTo('resources/cp/js/*.js'));
    }

    /**
     * @return array<int, class-string>
     */
    public function serializableClasses(): array
    {
        return $this->option('statamic.serializable_classes', []);
    }

    public function slug(): string
    {
        return $this->laravelFeature->slug();
    }

    /**
     * @return array<int, string>
     */
    public function stylesheets(): array
    {
        return $this->option('statamic.stylesheets') ?? File::glob($this->pathTo('resources/cp/css/*.css'));
    }

    /**
     * @return Collection<int, class-string<UpdateScript>>
     */
    public function updateScripts(): Collection
    {
        return $this->classesIn('UpdateScripts', UpdateScript::class);
    }

    /**
     * @return array<string, mixed>|string|array<int, string>|null
     */
    public function vite(): array|string|null
    {
        return $this->option('statamic.vite');
    }

    /**
     * @param  class-string|null  $type
     * @return Collection<int, class-string>
     */
    private function classesIn(string $folder, ?string $type = null): Collection
    {
        return $this->laravelFeature->classes($folder, $type, depth: 0);
    }

    /**
     * @return Collection<int, string>
     */
    private function cpDirectories(): Collection
    {
        if (! File::isDirectory($path = $this->pathTo('resources/cp'))) {
            return collect();
        }

        return collect(File::directories($path));
    }

    private function findAddon(): ?Addon
    {
        return Addons::all()
            ->first(fn (Addon $addon) => str_starts_with($this->laravelFeature->rootNamespace(), $addon->namespace().'\\'));
    }

    private function option(string $key, mixed $default = null): mixed
    {
        return $this->laravelFeature->option($key, $default);
    }
}
