---
name: release
description: Cut a manual tagged release of the laravel-property-bag package (private VCS distribution, no Packagist).
---

# Release

`agentsoftware/laravel-property-bag` is **not published to Packagist**. It's
distributed privately as a Composer VCS repository, and releases are plain git
tags — there is no publish step, no release pipeline, and no automation that
watches for tags. This skill is a guide for a human decision; it does not
create or push tags on its own. Always confirm the version bump and the tag
message with the user before running any tagging command.

## 1. Ensure `main` is green

Before tagging, run the full quality gate described in the `check` skill
(`vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `vendor/bin/phpunit`),
ideally reproducing the full CI matrix via `docker compose run --rm php82` /
`php83` / `php84` / `php85`, since a plain local run only exercises the
Laravel 12 line (see the `check` skill for why). Confirm the branch you're
releasing from is up to date with `origin/main` and that `.github/workflows/tests.yml`
is passing on it.

## 2. Pick the semver bump

Existing tags follow `vMAJOR.MINOR.PATCH` (e.g. `v1.5.0` is the latest at time
of writing). Pick the next version using standard semver rules:

- **Patch** (`vX.Y.Z+1`) — bug fixes, no API changes.
- **Minor** (`vX.Y+1.0`) — backwards-compatible additions (new methods, new
  optional config).
- **Major** (`vX+1.0.0`) — breaking changes. The Laravel 13 modernisation on
  `chore/modernise-laravel-13` (raising the PHP floor to 8.2, dropping support
  for old PHP/Laravel versions, converting tests to Orchestra Testbench,
  config-driven model overrides, native type declarations across `src/`) is a
  breaking change relative to the `v1.x` line, so the first release to include
  it should be tagged `v2.0.0`, not a `v1.x` patch/minor.

## 3. Create and push an annotated tag

```bash
git tag -a v2.0.0 -m "v2.0.0"
git push origin v2.0.0
```

Use an annotated tag (`-a`), not a lightweight one, so it carries a message
and tagger metadata. Only push the tag once the user has confirmed the
version number — pushing a tag to a shared remote is effectively irreversible
(consumers may already resolve it by the time it's noticed to be wrong).

## 4. How consumers pull the release

Because the package isn't on Packagist, consumers must point Composer at this
repository directly via a `vcs` entry in their own `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/agentsoftware/laravel-property-bag"
        }
    ],
    "require": {
        "agentsoftware/laravel-property-bag": "^2.0"
    }
}
```

Then:

```bash
composer require agentsoftware/laravel-property-bag:^2.0
```

Composer resolves the tag directly from the git repository — there's no
Packagist mirror to refresh, so the tag is live for consumers as soon as it's
pushed.
