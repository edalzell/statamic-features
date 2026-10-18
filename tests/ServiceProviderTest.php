<?php

use Edalzell\Features\Contracts\Extension;
use Edalzell\StatamicFeatures\StatamicAddons;

it('tags the extension', function () {
    expect(collect(app()->tagged(Extension::TAG))->map(fn (Extension $extension) => $extension::class)->all())
        ->toContain(StatamicAddons::class);
});
