<?php

namespace Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Tags;

use Statamic\Tags\Tags;

class SampleTag extends Tags
{
    public function index(): string
    {
        return 'sample';
    }
}
