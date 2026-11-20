<?php

namespace Edalzell\StatamicFeatures\Tests\Fixtures\Sample;

use Edalzell\Features\Providers\FeatureServiceProvider;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Scopes\SampleScope;
use Statamic\Facades\Entry;

class ServiceProvider extends FeatureServiceProvider
{
    protected function options(): array
    {
        return [
            'statamic.query_scopes' => [Entry::class => [SampleScope::class]],
        ];
    }
}
