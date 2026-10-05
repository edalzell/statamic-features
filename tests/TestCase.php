<?php

namespace Edalzell\StatamicFeatures\Tests;

use Edalzell\Features\ServiceProvider as FeaturesServiceProvider;
use Edalzell\StatamicFeatures\Tests\Fixtures\Addon\Features\Owned;
use Edalzell\StatamicFeatures\Tests\Fixtures\Bare;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample;
use Facades\Statamic\Version;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Statamic\Addons\Manifest;
use Statamic\Console\Processes\Composer;
use Statamic\Facades\CP\Nav;
use Statamic\Providers\StatamicServiceProvider;
use Statamic\Statamic;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Version::shouldReceive('get')->andReturn(Composer::create(dirname(__DIR__))->installedVersion(Statamic::PACKAGE));
        Nav::shouldReceive('build')->andReturn(collect());
        Nav::shouldReceive('clearCachedUrls');
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('statamic.users.repository', 'file');
        $app['config']->set('statamic.stache.watcher', false);
        $app['config']->set('statamic.routes.middleware', ['web', 'statamic-routes']);

        $app->make(Manifest::class)->manifest = [
            'edalzell/fixture-addon' => [
                'id' => 'edalzell/fixture-addon',
                'slug' => 'fixture-addon',
                'version' => 'dev-main',
                'namespace' => 'Edalzell\StatamicFeatures\Tests\Fixtures\Addon',
                'autoload' => 'src',
                'provider' => null,
            ],
        ];
    }

    protected function getPackageProviders($app)
    {
        return [
            StatamicServiceProvider::class,
            InertiaServiceProvider::class,
            FeaturesServiceProvider::class,
            Bare\ServiceProvider::class,
            Owned\ServiceProvider::class,
            Sample\ServiceProvider::class,
        ];
    }
}
