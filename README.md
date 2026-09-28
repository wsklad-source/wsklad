# WSKLAD

A WordPress plugin for connecting a store to [Moy Sklad](https://moysklad.ru).

WSKLAD is currently a **core, not a product**. It provides the connection layer —
accounts, credentials, storage, an admin interface, and about 90 hooks for
extensions to build on. Product synchronisation (products, orders, stock) lives
in extensions, not in this repository. If you are looking for order
synchronisation out of the box, this is not it yet.

- **Version:** 0.10.0
- **Requires PHP:** 7.4 or newer
- **Requires WordPress:** 6.0 or newer
- **License:** GPL-3.0-or-later ([`LICENSE`](LICENSE))

<!-- Badges are placeholders. The workflow that produces them is not in this
     repository yet, and a badge pointing at a workflow that does not exist is
     worse than no badge: it is a red light nobody can act on. Replace each
     `REPLACE_ME` with the real workflow file name and status URL when the badge
     is generated. -->

![CI](https://github.com/wsklad/wsklad/actions/workflows/ci.yml/badge.svg?branch=release/0.x)
![License: GPL-3.0-or-later](https://img.shields.io/badge/License-GPLv3-blue.svg)
![PHP >= 7.4](https://img.shields.io/badge/PHP-%3E%3D%207.4-8892BF.svg)
![WordPress >= 6.0](https://img.shields.io/badge/WordPress-%3E%3D%206.0-21759B.svg)
![Latest release](https://img.shields.io/badge/release-0.10.0-orange.svg)
![License](https://img.shields.io/badge/license-GPL--3.0--or--later-blue.svg)

## Install

From WordPress.org: **Plugins → Add New → search "WSKLAD" → Install → Activate.**

From a checkout:

```bash
git clone https://github.com/wsklad/wsklad.git
cd wsklad
composer install --no-dev
```

`composer install` is not optional. The plugin ships its dependencies in the
release ZIP, and there is no autoloader fallback.

On activation the plugin creates two tables, sets the connection defaults, and
generates the per-install encryption salt. Add a Moy Sklad account under
**WSKLAD → Add**.

Moy Sklad changed its authentication model on 1 December 2026: basic
authentication with a login and password costs 4 request-weight units per call
against 1 for a token, so `login` connections lose roughly three quarters of
their throughput. **Use a token.** The plugin has no code to exchange a
login/password for a permanent token; add the account by token instead.

## Documentation

| | |
| --- | --- |
| [`CHANGELOG.md`](CHANGELOG.md) | What changed, and when |
| [`SECURITY.md`](SECURITY.md) | Reporting a vulnerability, and the response time |
| [`SUPPORTED_VERSIONS.md`](SUPPORTED_VERSIONS.md) | Which lines get fixes |
| [`CONTRIBUTING.md`](CONTRIBUTING.md) | How to run the checks before opening a pull request |
| [`CODE_OF_CONDUCT.md`](CODE_OF_CONDUCT.md) | Expected behaviour of participants |
| [`docs/hooks.json`](docs/hooks.json) | Every hook the plugin fires, and where |
| [`plans/README.md`](plans/README.md) | Development plans, audit findings and known problems |

`plans/` is the most useful document here and the least likely to be read. It
contains a full audit of this codebase, including the unresolved findings. If you
are evaluating the plugin for production use, read
[`plans/00-AUDIT.md`](plans/00-AUDIT.md) first.

## Extending

WSKLAD is built to be extended without editing it. About 90 `do_action()` and
`apply_filters()` calls are documented in `docs/hooks.json`, with the file and
line of each one.

Two things to know before you subscribe to them:

- The hook contract is **not frozen**. WSKLAD is `0.x`; names may change without a
  major version bump. Pin your extension to a plugin version, not to a hook name.
- A hook name that fires from two places runs every subscriber twice, silently.
  The current list of those is in the CI output of `composer hooks:validate`.

The extension contract is not yet published. [`plans/40-PLAN-EXTENSION-PLATFORM.md`](plans/40-PLAN-EXTENSION-PLATFORM.md)
is the design; [`plans/EXTENDING.md`](plans/EXTENDING.md) is the working guide.

## Status

Not a stable release. There is no `1.0.0`, and the repository's own
[roadmap](plans/ROADMAP.md) puts it at roughly two years of part-time work away
with one maintainer. The gaps that matter to an evaluator — no
synchronisation engine, no REST/Ajax/cron substrate, no published extension
contract, several audit findings still open — are listed in `plans/README.md`
rather than smoothed over here.
