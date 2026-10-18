<?php

use Edalzell\StatamicFeatures\Tests\Fixtures\Addon\Features\Owned;
use Edalzell\StatamicFeatures\Tests\Fixtures\Addon\Features\Owned\UpdateScripts\OwnedScript;
use Edalzell\StatamicFeatures\Tests\Fixtures\Bare;
use Edalzell\StatamicFeatures\Tests\Fixtures\Bare\Tags\BareTag;
use Edalzell\StatamicFeatures\Tests\Fixtures\Options;
use Edalzell\StatamicFeatures\Tests\Fixtures\Orphan;
use Edalzell\StatamicFeatures\Tests\Fixtures\Orphan\UpdateScripts\OrphanScript;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Actions\SampleAction;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Dictionaries\SampleDictionary;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Fieldtypes\SampleFieldtype;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Modifiers\SampleModifier;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Query\Scopes\Filters\SampleFilter;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Scopes\SampleScope;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Tags\AbstractTag;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Tags\NotATag;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Tags\SampleTag;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\UpdateScripts\SampleScript;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Widgets\SampleWidget;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Statamic\Actions\Action;
use Statamic\Addons\Manifest;
use Statamic\Dictionaries\Dictionary;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Fieldset;
use Statamic\Fields\Fieldtype;
use Statamic\Modifiers\Modifier;
use Statamic\Query\Scopes\Scope;
use Statamic\Statamic;
use Statamic\Tags\Tags;
use Statamic\Widgets\Widget;

function samplePath(string $path): string
{
    return __DIR__.'/__fixtures__/Sample/'.$path;
}

function extensions(string $type): array
{
    return app('statamic.extensions')[$type]->all();
}

/** Keys come from the fixture's `__DIR__`, which Windows spells with backslashes. */
function published(string $tag, string $provider = Sample\ServiceProvider::class): array
{
    return collect(ServiceProvider::pathsToPublish($provider, $tag))
        ->mapWithKeys(fn (string $to, string $from) => [tidy($from) => $to])
        ->all();
}

function tidy(string $path): string
{
    return str_replace('\\', '/', $path);
}

it('registers a feature\'s extensions', function (string $type, string $extension) {
    expect(extensions($type))->toContain($extension);
})->with([
    'action' => [Action::class, SampleAction::class],
    'dictionary' => [Dictionary::class, SampleDictionary::class],
    'fieldtype' => [Fieldtype::class, SampleFieldtype::class],
    'modifier' => [Modifier::class, SampleModifier::class],
    'scope' => [Scope::class, SampleScope::class],
    'nested query scope' => [Scope::class, SampleFilter::class],
    'tag' => [Tags::class, SampleTag::class],
    'widget' => [Widget::class, SampleWidget::class],
]);

it('registers extensions for a feature on the plain laravel-features provider', function () {
    expect(extensions(Tags::class))->toContain(BareTag::class)
        ->and(Fieldset::find('bare::plain'))->not->toBeNull();
});

it('wont register abstract extensions or classes of another type', function () {
    expect(extensions(Tags::class))
        ->not->toContain(AbstractTag::class)
        ->not->toContain(NotATag::class);
});

it('registers a feature\'s blueprints under its slug', function () {
    expect(Blueprint::find('sample::thing'))->not->toBeNull();
});

it('registers a feature\'s fieldsets under its slug', function () {
    expect(Fieldset::find('sample::common'))->not->toBeNull();
});

it('registers update scripts against the addon that owns the feature', function () {
    expect(app('statamic.update-scripts')->all())
        ->toContain(['class' => OwnedScript::class, 'package' => 'edalzell/fixture-addon']);
});

it('wont register update scripts for a feature no addon owns', function () {
    expect(app('statamic.update-scripts')->pluck('class'))->not->toContain(SampleScript::class);
});

it('registers and publishes a feature\'s scripts under its slug', function () {
    $script = samplePath('resources/cp/js/cp.js');

    expect(Statamic::availableScripts(request())['sample'])->toContain('cp.js?v='.filemtime($script))
        ->and(published('sample'))->toHaveKey(tidy($script), public_path('vendor/sample/js/cp.js'));
});

it('registers and publishes a feature\'s stylesheets under its slug', function () {
    $stylesheet = samplePath('resources/cp/css/cp.css');

    expect(Statamic::availableStyles(request())['sample'])->toContain('cp.css?v='.filemtime($stylesheet))
        ->and(published('sample'))->toHaveKey(tidy($stylesheet), public_path('vendor/sample/css/cp.css'));
});

it('registers no assets for a feature that has none', function () {
    expect(Statamic::availableScripts(request()))->not->toHaveKey('bare')
        ->and(Statamic::availableStyles(request()))->not->toHaveKey('bare')
        ->and(ServiceProvider::pathsToPublish(Bare\ServiceProvider::class))->toBe([]);
});

it('publishes a feature\'s publishables under its slug', function () {
    expect(published('sample'))->toHaveKey(tidy(samplePath('resources/cp/img')), public_path('vendor/sample/img'));
});

it('publishes every other resources/cp directory, not js and css', function () {
    expect(array_values(published('sample')))->toBe([
        public_path('vendor/sample/img'),
        public_path('vendor/sample/js/cp.js'),
        public_path('vendor/sample/css/cp.css'),
    ]);
});

it('lets a feature name its assets in options instead', function () {
    (new Options\ServiceProvider(app()))->boot();

    expect(Statamic::availableScripts(request())['options'])->toContain('cp.js?v='.filemtime(samplePath('resources/cp/js/cp.js')))
        ->and(Statamic::availableStyles(request()))->not->toHaveKey('options')
        ->and(published('options', Options\ServiceProvider::class))
        ->toHaveKey(tidy(samplePath('resources/cp/img/logo.svg')), public_path('vendor/options/logo.svg'));
});

it('publishes a feature\'s assets after Statamic is installed', function () {
    $command = mock(Command::class);
    $command->shouldReceive('call')->atLeast()->once()->with('vendor:publish', ['--force' => true, '--tag' => 'sample']);
    $command->shouldReceive('call')->never()->with('vendor:publish', ['--force' => true, '--tag' => 'bare']);
    $command->shouldReceive('call'); // other tests' features, since the callbacks are static

    Statamic::runAfterInstalledCallbacks($command);
});

it('registers a feature\'s vite build under its slug', function () {
    (new Options\ServiceProvider(app()))->boot();

    expect(Statamic::availableVites(request())['options'])->toMatchArray([
        'input' => 'resources/js/options.js',
        'buildDirectory' => 'vendor/options/build',
    ])->and(published('options', Options\ServiceProvider::class))
        ->toHaveKey(tidy(fixturePath('Options/public/build').'/'), public_path('vendor/options/build/'));
});

it('subscribes a feature\'s subscribers', function () {
    expect(Event::hasListeners('bare.subscribed'))->toBeTrue();
});

it('scans one level deep, like Statamic', function () {
    expect(extensions(Tags::class))->not->toContain(Sample\Tags\Nested\Boom::class);
});

it('boots a feature only once', function () {
    (new Owned\ServiceProvider(app()))->boot();

    expect(app('statamic.update-scripts')->where('class', OwnedScript::class))->toHaveCount(1);
});

it('refuses a second feature with the same slug', function () {
    (new ImpostorServiceProvider(app()))->boot();
})->throws(RuntimeException::class, 'both have the slug [bare]');

it('refuses to scan a feature without a namespace', function () {
    (new GlobalServiceProvider(app()))->boot();
})->throws(RuntimeException::class, 'has no namespace');

it('rebuilds the addon manifest before giving up on update scripts', function () {
    $this->partialMock(Manifest::class)->shouldReceive('build')->once();

    (new Orphan\ServiceProvider(app()))->boot();

    expect(app('statamic.update-scripts')->pluck('class'))->not->toContain(OrphanScript::class);
});

it('fails loudly when an asset named in options is missing', function () {
    (new MissingAssetServiceProvider(app()))->boot();
})->throws(RuntimeException::class, 'does not exist');

class ImpostorServiceProvider extends Sample\ServiceProvider
{
    protected function featuresPath(): string
    {
        return fixturePath('Sample');
    }

    protected function name(): string
    {
        return 'Bare';
    }
}

class GlobalServiceProvider extends Sample\ServiceProvider
{
    protected function featuresPath(): string
    {
        return fixturePath('Sample');
    }

    protected function name(): string
    {
        return 'Global';
    }
}

class MissingAssetServiceProvider extends Options\ServiceProvider
{
    protected function featuresPath(): string
    {
        return fixturePath('Options');
    }

    protected function name(): string
    {
        return 'Missing';
    }

    protected function options(): array
    {
        return ['statamic.scripts' => [fixturePath('Bare/resources/cp/js/nope.js')]];
    }
}
