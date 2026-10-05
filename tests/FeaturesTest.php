<?php

use Edalzell\StatamicFeatures\Tests\Fixtures\Addon\Features\Owned\UpdateScripts\OwnedScript;
use Edalzell\StatamicFeatures\Tests\Fixtures\Bare;
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
use Illuminate\Support\ServiceProvider;
use Statamic\Actions\Action;
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
    $script = samplePath('src/../resources/js/cp.js');

    expect(Statamic::availableScripts(request())['sample'])->toContain('cp.js?v='.md5_file($script))
        ->and(ServiceProvider::pathsToPublish(Sample\ServiceProvider::class, 'sample'))
        ->toHaveKey($script, public_path('vendor/sample/js/cp.js'));
});

it('registers and publishes a feature\'s stylesheets under its slug', function () {
    $stylesheet = samplePath('src/../resources/css/cp.css');

    expect(Statamic::availableStyles(request())['sample'])->toContain('cp.css?v='.md5_file($stylesheet))
        ->and(ServiceProvider::pathsToPublish(Sample\ServiceProvider::class, 'sample'))
        ->toHaveKey($stylesheet, public_path('vendor/sample/css/cp.css'));
});

it('registers no assets for a feature that has none', function () {
    expect(Statamic::availableScripts(request()))->not->toHaveKey('bare')
        ->and(Statamic::availableStyles(request()))->not->toHaveKey('bare')
        ->and(ServiceProvider::pathsToPublish(Bare\ServiceProvider::class))->toBe([]);
});

it('publishes a feature\'s publishables under its slug', function () {
    expect(ServiceProvider::pathsToPublish(Sample\ServiceProvider::class, 'sample'))
        ->toHaveKey(samplePath('src/../resources/img'), public_path('vendor/sample/img'));
});

it('publishes a feature\'s assets after Statamic is installed', function () {
    $command = mock(Command::class);
    $command->shouldReceive('call')->atLeast()->once()->with('vendor:publish', ['--force' => true, '--tag' => 'sample']);
    $command->shouldReceive('call')->never()->with('vendor:publish', ['--force' => true, '--tag' => 'bare']);

    Statamic::runAfterInstalledCallbacks($command);
});
