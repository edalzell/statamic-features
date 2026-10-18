<?php

use Edalzell\Features\Contracts\Extension;
use Edalzell\StatamicFeatures\Extensions;
use Edalzell\StatamicFeatures\Routes\Actions;
use Edalzell\StatamicFeatures\Routes\Cp;
use Edalzell\StatamicFeatures\Routes\Web;
use Edalzell\StatamicFeatures\ServiceProvider;

it('tags the extension', function () {
    expect(collect(app()->tagged(Extension::TAG))->map(fn (Extension $extension) => $extension::class)->all())
        ->toContain(Extensions::class);
});

it('sends every feature\'s web, cp and action routes through Statamic', function () {
    expect(config('features.route_groups'))
        ->toMatchArray(['web' => Web::class, 'cp' => Cp::class, 'actions' => Actions::class])
        ->toHaveKey('api');
});

it('keeps a web group the app changed', function () {
    config()->set('features.route_groups', ['web' => ['middleware' => ['web', 'custom']]]);

    (new ServiceProvider(app()))->fillRouteGroups();

    expect(config('features.route_groups'))
        ->toMatchArray(['web' => ['middleware' => ['web', 'custom']], 'cp' => Cp::class, 'actions' => Actions::class]);
});

it('resolves one Extensions instance, so a feature can be remembered', function () {
    expect(app(Extensions::class))->toBe(app(Extensions::class));
});
