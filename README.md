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
| `src/Actions`, `src/Dictionaries`, `src/Fieldtypes`, `src/Modifiers`, `src/Scopes`, `src/Query/Scopes`, `src/Tags`, `src/Widgets` | Every concrete class of the matching type is registered |
| `src/UpdateScripts` | Registered against the addon the feature lives in. Skipped for a feature in a site, which has no package version to update from |
| `resources/blueprints` | Blueprint namespace, named after the feature's slug: `my-feature::thing` |
| `resources/fieldsets` | Fieldset namespace, named after the feature's slug: `my-feature::common` |
| `routes/web.php` | Statamic web routes |
| `routes/cp.php` | Control panel routes |
| `routes/actions.php` | Action routes, prefixed with the feature's slug: `/!/my-feature/...` |

Routes go through Statamic because `Route::statamic()` only exists once it has booted, after a feature's route files load. The provider sets these three as `features.route_groups` entries for every feature; override `routeGroups()` on one feature's provider to change them for that feature. Other route files keep the groups laravel-features gives them.

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

Vite builds and settings blueprints aren't handled per feature — those stay with the addon or site that hosts it.

## Testing

```bash
composer test
```
