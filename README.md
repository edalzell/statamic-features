# Statamic Features

Self-contained features for Statamic, in an addon or in a site's own `features/` directory. Built on [edalzell/laravel-features](https://github.com/edalzell/laravel-features): everything that package wires up for a feature still applies, and this one adds what a Statamic addon would register for itself.

## Usage

```bash
composer require edalzell/statamic-features
```

This package is a laravel-features [extension](https://github.com/edalzell/laravel-features#extensions): its provider tags `Extensions`, and laravel-features hands every feature to it once the feature's Laravel pieces have booted. A feature keeps extending laravel-features' `FeatureServiceProvider` and just gains the folders below.

## What's registered

| Feature path | What happens |
|---|---|
| `src/Actions`, `src/Dictionaries`, `src/Fieldtypes`, `src/Modifiers`, `src/Scopes`, `src/Query/Scopes`, `src/Query/Scopes/Filters`, `src/Tags`, `src/Widgets` | Every concrete class of the matching type is registered. One level deep, as Statamic scans an addon |
| `src/Subscribers` | Every class is passed to `Event::subscribe()` |
| `src/UpdateScripts` | Registered against the addon the feature lives in. Skipped for a feature in a site, which has no package version to update from |
| `resources/blueprints` | Blueprint namespace, named after the feature's slug: `my-feature::thing` |
| `resources/fieldsets` | Fieldset namespace, named after the feature's slug: `my-feature::common` |
| `routes/web.php` | Statamic web routes |
| `routes/cp.php` | Control panel routes |
| `routes/actions.php` | Action routes, prefixed with the feature's slug: `/!/my-feature/...` |

## Routes
To change how a feature's route files load, override `routeGroups()` on its provider with the `web`, `cp` or `actions` key, as laravel-features [documents](https://github.com/edalzell/laravel-features#route-groups).

## Control panel assets

Put them under `resources/cp`: every `js/*.js` is registered as a control panel script and every `css/*.css` as a stylesheet, and any other directory is published as-is:

```
MyFeature/
└── resources/
    └── cp/
        ├── css/my-feature.css   -> Statamic::style('my-feature', …)
        ├── img/                 -> public/vendor/my-feature/img
        └── js/my-feature.js     -> Statamic::script('my-feature', …)
```

They land in `public/vendor/my-feature/`, are tagged `my-feature` for `vendor:publish`, and are published automatically by `statamic:install`. To name them yourself instead, return `statamic.scripts`, `statamic.stylesheets` (lists of paths) and `statamic.publishables` (`[from => to]`) from the feature's `options()`.

A Vite build is registered the way an addon's is, from a `statamic.vite` option — the entry point, or the same array an addon's `$vite` property takes:

```php
protected function options(): array
{
    return ['statamic.vite' => ['input' => 'resources/js/cp.js', 'publicDirectory' => 'public']];
}
```

## Middleware groups

Add middleware to a group the way an addon's `$middlewareGroups` does, from the feature's `options()`. `statamic.middleware_groups` adds to the end of a group, `statamic.prepend_middleware_groups` to the start, keeping the order you list them in:

```php
'statamic.middleware_groups' => ['web' => [TrackVisits::class]],
'statamic.prepend_middleware_groups' => ['statamic.web' => [HandleNotFound::class]],
```

## Serializable classes

Classes stored in the cache, must be listed in `cache.serializable_classes`. Add them to the feature's `options()`:

```php
'statamic.serializable_classes' => [Product::class],
```

## Testing

```bash
composer test
```
