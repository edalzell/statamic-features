<?php

namespace Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Scopes;

use Edalzell\StatamicFeatures\Attributes\AllowOn;
use Statamic\Facades\Entry;
use Statamic\Query\Scopes\Scope;

#[AllowOn(Entry::class)]
class SampleScope extends Scope
{
    public function apply($query, $values) {}
}
