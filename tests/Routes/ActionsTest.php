<?php

it('loads a plain feature\'s routes/actions.php prefixed with its slug', function () {
    expect(featureRoute('statamic.bare.ping')?->uri())->toBe('!/bare/ping');
});
