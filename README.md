# Statamic Features

Self-contained features for Statamic, in an addon or in a site's own `features/` directory. Built on [edalzell/laravel-features](https://github.com/edalzell/laravel-features): everything that package wires up for a feature still applies, and this one adds what a Statamic addon would register for itself.

## Usage

```bash
composer require edalzell/statamic-features
```

That's it. This package is a laravel-features [extension](https://github.com/edalzell/laravel-features#extensions): its provider tags `StatamicAddons`, and laravel-features hands every feature to it once the feature's Laravel pieces have booted. A feature keeps extending laravel-features' `FeatureServiceProvider` and just gains the folders below.

Extend this package's provider instead when a feature has control panel assets:

```php
namespace Features\Search;

use Edalzell\StatamicFeatures\Providers\FeatureServiceProvider;

class ServiceProvider extends FeatureServiceProvider {}
```

## What's registered

| Feature path | What happens |
|---|---|
| `src/Actions`, `src/Dictionaries`, `src/Fieldtypes`, `src/Modifiers`, `src/Scopes`, `src/Query/Scopes`, `src/Tags`, `src/Widgets` | Every concrete class of the matching type is registered |
| `src/UpdateScripts` | Registered against the addon the feature lives in. Skipped for a feature in a site, which has no package version to update from |
| `resources/blueprints` | Blueprint namespace, named after the feature's slug: `my-feature::thing` |
| `resources/fieldsets` | Fieldset namespace, named after the feature's slug: `my-feature::common` |
| `routes/web.php` | Statamic web routes |
| `routes/cp.php` | Control panel routes |
| `routes/actions.php` | Action routes, prefixed with the feature's slug: `/!/my-feature/...` |

Routes go through Statamic because `Route::statamic()` only exists once it has booted, after a feature's route files load. The provider sets these three as `features.route_groups` entries for every feature; override `routeGroups()` on one feature's provider to change them for that feature. Other route files keep the groups laravel-features gives them.

## Control panel assets

The same properties an addon's provider has, published under the feature's slug rather than a package name. They need this package's provider; on laravel-features' own, return them from `options()` as `statamic.publishables`, `statamic.scripts` and `statamic.stylesheets`.

```php
class ServiceProvider extends FeatureServiceProvider
{
    protected array $publishables = [__DIR__.'/../resources/img' => 'img'];

    protected array $scripts = [__DIR__.'/../resources/js/cp.js'];

    protected array $stylesheets = [__DIR__.'/../resources/css/cp.css'];
}
```

These land in `public/vendor/my-feature/`, are tagged `my-feature` for `vendor:publish`, and are published automatically by `statamic:install`.

Vite builds and settings blueprints aren't handled per feature — those stay with the addon or site that hosts it.

## Testing

```bash
composer test
```
