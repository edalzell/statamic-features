<?php

use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Actions\SampleAction;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Dictionaries\SampleDictionary;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Fieldtypes\SampleFieldtype;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Modifiers\SampleModifier;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Query\Scopes\Filters\SampleFilter;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Scopes\SampleScope;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Tags\AbstractTag;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Tags\NotATag;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Tags\SampleTag;
use Edalzell\StatamicFeatures\Tests\Fixtures\Sample\Widgets\SampleWidget;
use Statamic\Actions\Action;
use Statamic\Dictionaries\Dictionary;
use Statamic\Fields\Fieldtype;
use Statamic\Modifiers\Modifier;
use Statamic\Query\Scopes\Scope;
use Statamic\Tags\Tags;
use Statamic\Widgets\Widget;

function extensions(string $type): array
{
    return app('statamic.extensions')[$type]->all();
}

it('registers a feature\'s extensions', function (string $type, string $extension) {
    expect(extensions($type))->toContain($extension);
})->with([
    'action' => [Action::class, SampleAction::class],
    'dictionary' => [Dictionary::class, SampleDictionary::class],
    'fieldtype' => [Fieldtype::class, SampleFieldtype::class],
    'modifier' => [Modifier::class, SampleModifier::class],
    'scope' => [Scope::class, SampleScope::class],
    'nested query scope' => [Scope::class, SampleFilter::class],
    'tag' => [Tags::class, SampleTag::class],
    'widget' => [Widget::class, SampleWidget::class],
]);

it('wont register abstract extensions or classes of another type', function () {
    expect(extensions(Tags::class))
        ->not->toContain(AbstractTag::class)
        ->not->toContain(NotATag::class);
});
