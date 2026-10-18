<?php

use Edalzell\Features\Contracts\Extension;
use Edalzell\StatamicFeatures\Routes\Actions;
use Edalzell\StatamicFeatures\Routes\Cp;
use Edalzell\StatamicFeatures\Routes\Web;
use Edalzell\StatamicFeatures\StatamicAddons;

it('tags the extension', function () {
    expect(collect(app()->tagged(Extension::TAG))->map(fn (Extension $extension) => $extension::class)->all())
        ->toContain(StatamicAddons::class);
});

it('sends every feature\'s web, cp and action routes through Statamic', function () {
    expect(config('features.route_groups'))
        ->toMatchArray(['web' => Web::class, 'cp' => Cp::class, 'actions' => Actions::class]);
});
