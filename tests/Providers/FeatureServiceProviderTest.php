<?php

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

function featureRoute(string $name): ?RoutingRoute
{
    return Route::getRoutes()->getByName($name);
}

it('loads routes/web.php as Statamic web routes', function () {
    expect(featureRoute('sample.web')?->gatherMiddleware())->toContain('statamic-routes');
});

it('loads routes/cp.php as control panel routes', function () {
    $route = featureRoute('statamic.cp.sample.cp');

    expect($route?->uri())->toBe('cp/sample-cp')
        ->and($route?->gatherMiddleware())->toContain('statamic.cp.authenticated');
});

it('loads routes/actions.php as action routes prefixed with the feature slug', function () {
    expect(featureRoute('statamic.sample.ping')?->uri())->toBe('!/sample/ping');
});

it('leaves other route files to their usual groups', function () {
    expect(featureRoute('sample.api')?->uri())->toBe('api/sample-api');
});
