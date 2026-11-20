<?php

use Edalzell\Features\Features;
use Edalzell\StatamicFeatures\StatamicFeature;
use Edalzell\StatamicFeatures\Tests\Fixtures\Options;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Scopes\SampleScope;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Tags\SampleTag;

function feature(string $provider = Sample\ServiceProvider::class): StatamicFeature
{
    $provider = app()->getProvider($provider) ?? new $provider(app());
    $options = (new ReflectionMethod($provider, 'options'))->invoke($provider);

    return new StatamicFeature((new Features($provider))->options($options));
}

it('finds the extensions one level deep', function () {
    expect(feature()->extensions())
        ->toContain(SampleScope::class, SampleTag::class)
        ->not->toContain(Sample\Tags\Nested\Boom::class);
});

it('reads control panel assets from resources/cp by convention', function () {
    $feature = feature();

    expect(array_map('basename', $feature->scripts()))->toBe(['cp.js'])
        ->and(array_map('basename', $feature->stylesheets()))->toBe(['cp.css'])
        ->and(array_values($feature->publishables()))->toBe(['img']);
});

it('prefers assets named in options', function () {
    $feature = feature(Options\ServiceProvider::class);

    expect(array_map('basename', $feature->scripts()))->toBe(['cp.js'])
        ->and($feature->stylesheets())->toBe([])
        ->and($feature->vite())->toBe('resources/js/options.js');
});

it('publishes under the feature\'s slug', function () {
    expect(feature()->publicPath('img/logo.svg'))->toBe(public_path('vendor/sample/img/logo.svg'));
});
