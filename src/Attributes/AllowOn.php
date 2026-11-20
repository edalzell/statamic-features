<?php

namespace Edalzell\StatamicFeatures\Attributes;

use Attribute;

/**
 * The repositories a query scope is allowed on, as `Entry::allowQueryScope()` would.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AllowOn
{
    /** @var array<int, class-string> */
    public readonly array $repositories;

    /** @param  class-string  ...$repositories */
    public function __construct(string ...$repositories)
    {
        $this->repositories = array_values($repositories);
    }
}
