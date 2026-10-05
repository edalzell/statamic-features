<?php

namespace Edalzell\StatamicFeatures\Tests\Fixtures\Sample;

use Edalzell\StatamicFeatures\Providers\FeatureServiceProvider;

class ServiceProvider extends FeatureServiceProvider
{
    protected array $scripts = [__DIR__.'/../resources/js/cp.js'];

    protected array $stylesheets = [__DIR__.'/../resources/css/cp.css'];
}
