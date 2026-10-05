<?php

namespace Edalzell\StatamicFeatures;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
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

class Features
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

    private string $namespace;

    private string $path;

    /** @var array<string, string> */
    private array $publishables = [];

    /** @var array<int, string> */
    private array $scripts = [];

    private string $slug;

    /** @var array<int, string> */
    private array $stylesheets = [];

    public function __construct(private readonly ServiceProvider $provider)
    {
        $reflection = new ReflectionClass($provider);

        $this->namespace = $reflection->getNamespaceName();
        $this->path = dirname($reflection->getFileName(), 2);
        $this->slug = str(basename($this->path))->kebab()->toString();
    }

    public function bootBlueprints(): static
    {
        if (File::isDirectory($path = "{$this->path}/resources/blueprints")) {
            Blueprint::addNamespace($this->slug, $path);
        }

        return $this;
    }

    public function bootExtensions(): static
    {
        collect(self::EXTENSIONS)
            ->flatMap(fn (string $type, string $folder) => $this->classesIn($folder, $type))
            ->each(fn (string $extension) => $extension::register());

        return $this;
    }

    public function bootFeature(): void
    {
        $this
            ->bootBlueprints()
            ->bootExtensions()
            ->bootFieldsets()
            ->bootPublishables()
            ->bootPublishAfterInstall()
            ->bootScripts()
            ->bootStylesheets()
            ->bootUpdateScripts();
    }

    public function bootFieldsets(): static
    {
        if (File::isDirectory($path = "{$this->path}/resources/fieldsets")) {
            Fieldset::addNamespace($this->slug, $path);
        }

        return $this;
    }

    public function bootPublishables(): static
    {
        if (! empty($this->publishables)) {
            $this->publish(array_map($this->publicPath(...), $this->publishables));
        }

        return $this;
    }

    /**
     * `statamic:install` publishes an addon's assets by the addon's tag; a
     * feature's are tagged with its own slug, so it has to ask for them itself.
     */
    public function bootPublishAfterInstall(): static
    {
        if (! empty([...$this->publishables, ...$this->scripts, ...$this->stylesheets])) {
            Statamic::afterInstalled($this->publishAssets(...));
        }

        return $this;
    }

    public function bootScripts(): static
    {
        collect($this->scripts)
            ->each(fn (string $path) => Statamic::script($this->slug, $this->publishAsset($path, 'js')));

        return $this;
    }

    public function bootStylesheets(): static
    {
        collect($this->stylesheets)
            ->each(fn (string $path) => Statamic::style($this->slug, $this->publishAsset($path, 'css')));

        return $this;
    }

    /**
     * An update script runs when a composer package changes version, so only a
     * feature inside an addon has anything to run against.
     */
    public function bootUpdateScripts(): static
    {
        if (is_null($addon = $this->addon())) {
            return $this;
        }

        $this
            ->classesIn('UpdateScripts', UpdateScript::class)
            ->each(fn (string $script) => $script::register($addon->package()));

        return $this;
    }

    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /** @param  array<string, string>  $publishables */
    public function publishables(array $publishables): static
    {
        $this->publishables = $publishables;

        return $this;
    }

    /** @param  array<int, string>  $scripts */
    public function scripts(array $scripts): static
    {
        $this->scripts = $scripts;

        return $this;
    }

    public function slug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /** @param  array<int, string>  $stylesheets */
    public function stylesheets(array $stylesheets): static
    {
        $this->stylesheets = $stylesheets;

        return $this;
    }

    private function addon(): ?Addon
    {
        return Addons::all()
            ->first(fn (Addon $addon) => str_starts_with($this->namespace, $addon->namespace().'\\'));
    }

    /**
     * @param  class-string  $type
     * @return Collection<int, class-string>
     */
    private function classesIn(string $folder, string $type): Collection
    {
        if (! File::isDirectory($path = "{$this->path}/src/{$folder}")) {
            return collect();
        }

        return collect(File::allFiles($path))
            ->map(fn (SplFileInfo $file) => $this->className($folder, $file))
            ->filter(fn (string $class) => $this->isConcrete($class, $type))
            ->values();
    }

    private function className(string $folder, SplFileInfo $file): string
    {
        return str("{$folder}/{$file->getRelativePathname()}")
            ->beforeLast('.php')
            ->replace('/', '\\')
            ->prepend($this->namespace.'\\')
            ->toString();
    }

    /** @param  class-string  $type */
    private function isConcrete(string $class, string $type): bool
    {
        return is_subclass_of($class, $type) && ! (new ReflectionClass($class))->isAbstract();
    }

    private function publicPath(string $path): string
    {
        return public_path("vendor/{$this->slug}/{$path}");
    }

    /** @param  array<string, string>  $paths */
    private function publish(array $paths): void
    {
        (new ReflectionMethod($this->provider, 'publishes'))->invoke($this->provider, $paths, $this->slug);
    }

    /**
     * Versioned by the file's contents rather than a package version, which a
     * feature in a site doesn't have.
     */
    private function publishAsset(string $path, string $folder): string
    {
        $this->publish([$path => $this->publicPath("{$folder}/".basename($path))]);

        return basename($path).'?v='.md5_file($path);
    }

    private function publishAssets(Command $command): void
    {
        $command->call('vendor:publish', ['--force' => true, '--tag' => $this->slug]);
    }
}
