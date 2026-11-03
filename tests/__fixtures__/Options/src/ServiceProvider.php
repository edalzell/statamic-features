<?php

namespace Edalzell\StatamicFeatures\Tests\Fixtures\Options;

use Edalzell\Features\Providers\FeatureServiceProvider;

/** Names its assets in options instead of keeping them under resources/cp. */
class ServiceProvider extends FeatureServiceProvider
{
    protected function options(): array
    {
        $sample = dirname(__DIR__, 2).'/Sample/resources/cp';

        return [
            'statamic.publishables' => ["{$sample}/img/logo.svg" => 'logo.svg'],
            'statamic.scripts' => ["{$sample}/js/cp.js"],
            'statamic.serializable_classes' => [self::class],
            'statamic.stylesheets' => [],
            'statamic.vite' => 'resources/js/options.js',
        ];
    }
}
