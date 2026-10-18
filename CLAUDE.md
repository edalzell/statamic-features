Extension for edalzell/laravel-features. Once a feature's Laravel pieces have booted, `Extensions` registers its Statamic pieces the way Statamic's `AddonServiceProvider` does for an addon: the classes under `src/Actions`, `Dictionaries`, `Fieldtypes`, `Modifiers`, `Scopes`, `Query/Scopes`, `Tags`, `Widgets`, `UpdateScripts`, plus `resources/blueprints` and `resources/fieldsets` as namespaces under the feature's slug.

`ServiceProvider` only tags the extension. `Providers\FeatureServiceProvider` is optional: it adds the addon-style `$publishables`/`$scripts`/`$stylesheets` properties (passed on as `statamic.*` options) and routes `web`, `cp` and `actions` files through Statamic.

# Development

* methods and properties grouped public, protected, private; alphabetical within each group
* tests mirror `src`; fixtures live in `tests/__fixtures__` and boot a real Statamic via `TestCase`
