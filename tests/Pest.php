<?php

use Edalzell\StatamicFeatures\Tests\TestCase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

uses(TestCase::class)->in(__DIR__);

function featureRoute(string $name): ?RoutingRoute
{
    return Route::getRoutes()->getByName($name);
}

function fixturePath(string $path = ''): string
{
    return rtrim(__DIR__.'/__fixtures__/'.$path, '/');
}
