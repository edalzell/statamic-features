<?php

namespace Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Modifiers;

use Statamic\Modifiers\Modifier;

class SampleModifier extends Modifier
{
    public function index(mixed $value): mixed
    {
        return $value;
    }
}
