<?php

it('leaves other route files to their usual groups', function () {
    expect(featureRoute('sample.api')?->uri())->toBe('api/sample-api');
});
