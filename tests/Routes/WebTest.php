<?php

it('loads a plain feature\'s routes/web.php as Statamic web routes', function () {
    expect(featureRoute('bare.web')?->gatherMiddleware())->toContain('statamic-routes');
});
