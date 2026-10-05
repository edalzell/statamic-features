<?php

namespace Edalzell\StatamicFeatures\Tests\Fixtures\Addon\Features\Owned\UpdateScripts;

use Statamic\UpdateScripts\UpdateScript;

class OwnedScript extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion): bool
    {
        return false;
    }

    public function update(): void {}
}
