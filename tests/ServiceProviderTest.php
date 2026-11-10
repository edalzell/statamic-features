<?php

use Edalzell\Features\Extension;
use Edalzell\StatamicFeatures\Extensions;

it('tags the extension', function () {
    expect(collect(app()->tagged(Extension::TAG))->map(fn (Extension $extension) => $extension::class)->all())
        ->toContain(Extensions::class);
});

it('resolves one Extensions instance, so a feature can be remembered', function () {
    expect(app(Extensions::class))->toBe(app(Extensions::class));
});

it('records that Statamic has booted', function () {
    expect(app()->bound('statamic.booted'))->toBeTrue();
});
