<?php

it('loads a plain feature\'s routes/cp.php as control panel routes, with $router available', function () {
    $route = featureRoute('statamic.cp.bare.cp');

    expect($route?->uri())->toBe('cp/bare-cp')
        ->and($route?->gatherMiddleware())->toContain('statamic.cp.authenticated');
});
