<?php

namespace Edalzell\StatamicFeatures\Tests\Fixtures\Sample\UpdateScripts;

use Statamic\UpdateScripts\UpdateScript;

class SampleScript extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion): bool
    {
        return false;
    }

    public function update(): void {}
}
