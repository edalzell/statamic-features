<?php

namespace Edalzell\StatamicFeatures\Providers;

use Edalzell\Features\Providers\FeatureServiceProvider as BaseProvider;

/**
 * Optional. Any feature gets its Statamic pieces and routes through the
 * package's provider; extending this instead adds the addon-style asset
 * properties.
 */
abstract class FeatureServiceProvider extends BaseProvider
{
    /** @var array<string, string> */
    protected array $publishables = [];

    /** @var array<int, string> */
    protected array $scripts = [];

    /** @var array<int, string> */
    protected array $stylesheets = [];

    /** @return array<string, mixed> */
    protected function options(): array
    {
        return [
            'statamic.publishables' => $this->publishables,
            'statamic.scripts' => $this->scripts,
            'statamic.stylesheets' => $this->stylesheets,
        ];
    }
}
