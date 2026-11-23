<?php

namespace Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Query\Scopes;

use Edalzell\StatamicFeatures\Attributes\AllowOn;
use Statamic\Facades\Entry;
use Statamic\Facades\Term;
use Statamic\Query\Scopes\Scope;

#[AllowOn(Entry::class)]
#[AllowOn(Term::class)]
class RepeatedScope extends Scope
{
    public function apply($query, $values) {}
}
