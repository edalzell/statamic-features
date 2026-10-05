<?php

namespace Edalzell\StatamicFeatures;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use Statamic\Actions\Action;
use Statamic\Dictionaries\Dictionary;
use Statamic\Facades\Blueprint;
use Statamic\Fields\Fieldtype;
use Statamic\Modifiers\Modifier;
use Statamic\Query\Scopes\Scope;
use Statamic\Tags\Tags;
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

    private string $slug;

    public function __construct(ServiceProvider $provider)
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
            ->bootExtensions();
    }

    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function slug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
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
}
