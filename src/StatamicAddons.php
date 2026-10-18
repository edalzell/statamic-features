<?php

namespace Edalzell\StatamicFeatures;

use Edalzell\Features\Contracts\Extension;
use Edalzell\Features\Features;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;
use Statamic\Actions\Action;
use Statamic\Addons\Addon;
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
use Symfony\Component\Finder\SplFileInfo;

/**
 * What Statamic's AddonServiceProvider does for an addon, done for a feature.
 * laravel-features hands every feature here once its Laravel pieces have booted;
 * Statamic's own registries only know the app's and each addon's, so without
 * this a feature's tags, fieldtypes and the rest go unseen.
 */
class StatamicAddons implements Extension
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
        'Scopes' => Scope::class,
        'Tags' => Tags::class,
        'Widgets' => Widget::class,
    ];

    public function boot(Features $features): void
    {
        Statamic::booted(fn () => $this->bootFeature($features));
    }

    public function bootBlueprints(Features $features): static
    {
        if (File::isDirectory($path = $features->disk()->path('resources/blueprints'))) {
            Blueprint::addNamespace($features->slug(), $path);
        }

        return $this;
    }

    public function bootExtensions(Features $features): static
    {
        collect(self::EXTENSIONS)
            ->flatMap(fn (string $type, string $folder) => $this->classesIn($features, $folder, $type))
            ->each(fn (string $extension) => $extension::register());

        return $this;
    }

    public function bootFeature(Features $features): void
    {
        $this
            ->bootBlueprints($features)
            ->bootExtensions($features)
            ->bootFieldsets($features)
            ->bootPublishables($features)
            ->bootPublishAfterInstall($features)
            ->bootScripts($features)
            ->bootStylesheets($features)
            ->bootUpdateScripts($features);
    }

    public function bootFieldsets(Features $features): static
    {
        if (File::isDirectory($path = $features->disk()->path('resources/fieldsets'))) {
            Fieldset::addNamespace($features->slug(), $path);
        }

        return $this;
    }

    public function bootPublishables(Features $features): static
    {
        if (! empty($publishables = $this->publishables($features))) {
            $this->publish($features, array_map(fn (string $path) => $this->publicPath($features, $path), $publishables));
        }

        return $this;
    }

    /**
     * `statamic:install` publishes an addon's assets by the addon's tag; a
     * feature's are tagged with its own slug, so it has to ask for them itself.
     */
    public function bootPublishAfterInstall(Features $features): static
    {
        if (! empty([...$this->publishables($features), ...$this->scripts($features), ...$this->stylesheets($features)])) {
            Statamic::afterInstalled(fn (Command $command) => $this->publishAssets($features, $command));
        }

        return $this;
    }

    public function bootScripts(Features $features): static
    {
        collect($this->scripts($features))
            ->each(fn (string $path) => Statamic::script($features->slug(), $this->publishAsset($features, $path, 'js')));

        return $this;
    }

    public function bootStylesheets(Features $features): static
    {
        collect($this->stylesheets($features))
            ->each(fn (string $path) => Statamic::style($features->slug(), $this->publishAsset($features, $path, 'css')));

        return $this;
    }

    /**
     * An update script runs when a composer package changes version, so only a
     * feature inside an addon has anything to run against.
     */
    public function bootUpdateScripts(Features $features): static
    {
        if (is_null($addon = $this->addon($features))) {
            return $this;
        }

        $this
            ->classesIn($features, 'UpdateScripts', UpdateScript::class)
            ->each(fn (string $script) => $script::register($addon->package()));

        return $this;
    }

    private function addon(Features $features): ?Addon
    {
        return Addons::all()
            ->first(fn (Addon $addon) => str_starts_with($features->rootNamespace(), $addon->namespace().'\\'));
    }

    /**
     * @param  class-string  $type
     * @return Collection<int, class-string>
     */
    private function classesIn(Features $features, string $folder, string $type): Collection
    {
        if (! File::isDirectory($path = $features->disk()->path("src/{$folder}"))) {
            return collect();
        }

        return collect(File::allFiles($path))
            ->map(fn (SplFileInfo $file) => $this->className($features, $folder, $file))
            ->filter(fn (string $class) => $this->isConcrete($class, $type))
            ->values();
    }

    private function className(Features $features, string $folder, SplFileInfo $file): string
    {
        return str("{$folder}/{$file->getRelativePathname()}")
            ->beforeLast('.php')
            ->replace('/', '\\')
            ->prepend($features->rootNamespace().'\\')
            ->toString();
    }

    /** @param  class-string  $type */
    private function isConcrete(string $class, string $type): bool
    {
        return is_subclass_of($class, $type) && ! (new ReflectionClass($class))->isAbstract();
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
     * Versioned by the file's contents rather than a package version, which a
     * feature in a site doesn't have.
     */
    private function publishAsset(Features $features, string $path, string $folder): string
    {
        $this->publish($features, [$path => $this->publicPath($features, "{$folder}/".basename($path))]);

        return basename($path).'?v='.md5_file($path);
    }

    /** @return array<string, string> */
    private function publishables(Features $features): array
    {
        return $features->option('statamic.publishables', []);
    }

    private function publishAssets(Features $features, Command $command): void
    {
        $command->call('vendor:publish', ['--force' => true, '--tag' => $features->slug()]);
    }

    /** @return array<int, string> */
    private function scripts(Features $features): array
    {
        return $features->option('statamic.scripts', []);
    }

    /** @return array<int, string> */
    private function stylesheets(Features $features): array
    {
        return $features->option('statamic.stylesheets', []);
    }
}
