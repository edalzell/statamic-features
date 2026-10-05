<?php

namespace Edalzell\StatamicFeatures\Providers;

use Edalzell\Features\Providers\FeatureServiceProvider as BaseProvider;
use Edalzell\StatamicFeatures\Features;
use Illuminate\Contracts\Foundation\Application;
use Statamic\Statamic;

abstract class FeatureServiceProvider extends BaseProvider
{
    private Features $statamic;

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->statamic = (new Features($this))
            ->path($this->featuresPath())
            ->slug($this->slug());
    }

    public function boot(): void
    {
        parent::boot();

        Statamic::booted(fn () => $this->statamic->bootFeature());
    }
}
