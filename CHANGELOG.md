# Changelog

## v2.1.0 - 2026-11-08

### 🚀 New

- Allow a feature to register middleware groups [@marcorieser](https://github.com/marcorieser) (#23)

## v2.0.0 - 2026-11-03

### ⚠️ Breaking

- Use Extension for route groups [@edalzell](https://github.com/edalzell) (#15)

### 🚀 New

- Allow a feature to register serializable classes [@marcorieser](https://github.com/marcorieser) (#14)

### 🧰 Maintenance

- Use github-workflows v1.5.2 [@edalzell](https://github.com/edalzell) (#20)
- Add a Breaking section to release notes [@edalzell](https://github.com/edalzell) (#19)
- Improve docs [@edalzell](https://github.com/edalzell) (#17)
- Draft releases for merged fork PRs [@edalzell](https://github.com/edalzell) (#18)
- Run CI on pull requests [@edalzell](https://github.com/edalzell) (#16)
- Use Extension for route groups [@edalzell](https://github.com/edalzell) (#15)

## v1.0.1 - 2026-10-27

### 🧰 Maintenance

- Drop stale README claim that Vite isn't handled per feature [@edalzell](https://github.com/edalzell) (#10)
- Bump stefanzweifel/git-auto-commit-action from 7.2.0 to 7.2.1 in the github-actions group [@[dependabot[bot]](https://github.com/apps/dependabot)](https://github.com/[dependabot[bot]](https://github.com/apps/dependabot)) (#3)
- Use Laravel Features class discovery [@edalzell](https://github.com/edalzell) (#11)

## v1.0.0 - 2026-10-18

### 🚀 New

- Register a feature's Statamic tags, fieldtypes, modifiers, scopes, actions, widgets, dictionaries, subscribers and update scripts, and its blueprint and fieldset namespaces, through a laravel-features extension [@edalzell](https://github.com/edalzell) (#1)
- Load a feature's `routes/web.php`, `routes/cp.php` and `routes/actions.php` through Statamic [@edalzell](https://github.com/edalzell)
- Register and publish control panel assets from `resources/cp`, or from `statamic.*` options, including a Vite build [@edalzell](https://github.com/edalzell)
