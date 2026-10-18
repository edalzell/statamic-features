<?php

namespace Edalzell\StatamicFeatures\Tests\Fixtures\Orphan\UpdateScripts;

use Statamic\UpdateScripts\UpdateScript;

class OrphanScript extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return false;
    }

    public function update() {}
}
