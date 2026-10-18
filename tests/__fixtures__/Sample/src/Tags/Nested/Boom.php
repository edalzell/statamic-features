<?php

namespace Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Tags\Nested;

use RuntimeException;
use Statamic\Tags\Tags;

// Statamic scans an addon's folders one level deep; so does a feature.
throw new RuntimeException('Nested files must not be loaded');
class Boom extends Tags {}
