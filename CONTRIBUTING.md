# Contributing to WSKLAD

Thank you for looking. Two things to set expectations honestly before you start:

This is a one-person project with roughly one day a week of working time
(see [`plans/README.md`](plans/README.md) §0). Pull requests are read, but not
quickly, and a large refactor will not be merged. **A focused fix with a test is
worth more here than an ambitious patch.**

The project is at `0.10.0` and unstable by SemVer. The 1.0.0 boundary is where
the breaking changes go, not into patch releases.

## Getting set up

```bash
git clone https://github.com/wsklad/wsklad.git
cd wsklad
composer install
```

`composer install` is required even for tests: the test bootstrap loads
`vendor/autoload.php` so the plugin's own PSR-4 mapping resolves.

## Running the checks

All of them run in CI on every pull request. Run them locally first — a red CI run
costs a maintainer more than it costs you.

```bash
composer test              # PHPUnit, no coverage
composer test:unit         # the unit + security suites only
composer test:integration  # real WordPress + real database (see below)
composer lint:php          # parallel-lint, syntax only
composer lint:cs           # PHPCS: WordPress, WordPress-Docs, WordPress-Extra,
                           # Security, PHPCompatibilityWP
composer lint:all          # both linters
composer hooks:validate    # hook names: duplicates and namespace
composer settings:validate # settings declared in a form but read nowhere
```

`composer hooks:validate` needs no dependencies at all — it reads the source and
exits non-zero on a duplicate hook name. It is the fastest check here and the one
most likely to catch something you did not think about.

### What each check is for

| Check | Catches |
| --- | --- |
| `lint:php` | A parse error. On PHP 7.4, syntax that is fine on your PHP 8.4 |
| `lint:cs` | Unescaped output, unprepared SQL, loose comparison, PHP 8-only syntax |
| `hooks:validate` | A hook name fired from two places, or outside the `wsklad_` namespace |
| `settings:validate` | A setting the settings screen writes and nothing ever reads |
| `test` | Behaviour, and the contract tests that read `src/` and `views/` |
| `test:integration` | Anything a stub cannot reproduce. See below. |

## The integration suite is not optional

`tests/Integration/run.php` boots a real WordPress and runs the real SQL. It is the
only suite that found the bugs worth finding.

Concretely: credential encryption shipped **completely non-functional** for a full
development cycle with every unit test green. The installation salt was written as
raw binary into a `utf8mb4` options column, the write failed, the return value was
never checked, the salt read back empty, and the encryption key was therefore derived
afresh on every request — so a credential could be saved on one page load and never
read back on the next. No exception, no log line, no failing assertion.

Every unit test for the cryptography passed. They could not have caught it: the stub
is a PHP array, and a PHP array has no charset.

That is the whole argument for this suite. If you change anything in `src/Data/`,
`src/Security/` or `src/Queue/`, run it.

```bash
composer test:integration
# or, against a WordPress somewhere else:
WSKLAD_WP_PATH=/path/to/wordpress/ php tests/Integration/run.php
```

It needs a WordPress install and a reachable MySQL. It creates rows prefixed
`wsklad-itest-` and deletes them; it drops and rebuilds `wsklad_accounts` once, in the
self-heal section, which is the only way to prove the self-heal works. It never touches
a table it does not own.

The CI job `test-integration` runs exactly this against MySQL 8.

### The contract tests are unusual

`tests/Contract/` does not run the plugin. It reads its source and asserts on
what it finds: that every `getView()` names a real file, that no hook name fires
twice, that `docs/hooks.json` matches reality, that state-changing functions
verify a nonce.

**A contract test that fails is usually reporting a real defect, not a problem
with the test.** Read the failure message — it lists file and line. Two known
reasons one can be red on a clean checkout:

- `NoncesTest` matches on function *name*, so `getDateCreate()` is reported as a
  state-changing function. Those entries are candidates for review, not
  confirmed vulnerabilities. A nonce check delegated to a shared form base is
  also invisible to a static scan.
- `HooksTest` and `ViewsExistTest` fail on any pre-existing duplicate or missing
  view in `src/`. Those are found and being fixed separately; the tests stay red
  until they are.

Do not "fix" a failing contract test by relaxing its assertion. The assertion is
the deliverable.

## Adding a hook

1. Fire it from exactly one place. A name that fires twice runs every subscriber
   twice, with nothing in the log to say so.
2. Name it `wsklad_` and in lower snake case. An unprefixed action is fired in the
   global namespace, where every plugin on the site can respond to it.
3. Run `php tools/generate-hooks.php` and commit `docs/hooks.md`. It is generated;
   `php tools/generate-hooks.php --check` fails the build if it is stale.
4. Run `composer hooks:validate`.

To deprecate a hook name, mark the firing site `@deprecated <version> <hook_name>`,
where the identifier after the version is the **deprecated** name, not the one
replacing it. Keep the old name firing until `2.0.0` — an alias is what makes the
`0.x` line non-breaking, and deleting it early is the break the policy exists to
prevent.

## Adding a test

PHPUnit 9.6, no WordPress test suite. `tests/bootstrap.php` provides
hand-written stubs in `Wsklad\Testing\`:

- `WordPress` — options, capabilities, request context. `WordPress::reset()` in
  `setUp()` puts it all back.
- `Hooks` — a working hook registry. `Hooks::fired()` returns what actually
  fired, so a test can assert a hook ran once, not merely that a function was
  called.
- `FakeWpdb` — `prepare()` does real placeholder substitution with real quoting,
  and records every statement. Assert on the SQL string.
- `SourceScanner` — the token-level scanner behind the contract tests.

A stub that returned something plausible but different from the real WordPress
function would make the tests pass while proving nothing. If you add a stub, make
it behave like the original.

## Style

- Tabs, UTF-8, LF, final newline. `.editorconfig` says so; PHPCS enforces it.
- `defined('ABSPATH') || exit;` at the top of every PHP file outside `tests/`.
- Text domain `wsklad`, always. A literal that is not wrapped for translation is a
  bug, and `lint:cs` will say so.
- Do not bump the version in `wsklad.php`. That is the maintainer's job, in a
  `prepare/*` branch, and only at release time.

## What gets merged

| Yes | No |
| --- | --- |
| A bug fix with a regression test | A refactor with no behavioural change |
| A security fix | A new feature in a patch release |
| A test that fails for a real reason | A test relaxed to make a build green |
| A documentation correction | A change to files you do not own |

## Where things are

| Path | |
| --- | --- |
| `src/` | The plugin. PSR-4 `Wsklad\` |
| `views/` | Templates, included by `Views::getView()` |
| `tests/Unit`, `tests/Security` | PHPUnit, with a hand-written WordPress stub layer |
| `tests/Contract` | PHPUnit, reads the source and asserts on it |
| `tests/Integration` | Real WordPress + real database. Not PHPUnit — see above |
| `tools/` | Standalone scripts. Not shipped in the release |
| `docs/hooks.md` | The hook catalogue. Generated, do not hand-edit |
| `plans/` | Audit, plans, decisions. Not shipped in the release |

## Security

Do not report a vulnerability in a pull request or a public issue. See
[`SECURITY.md`](SECURITY.md).

## Licence

Contributions are accepted under the GPL-3.0-or-later, the licence of the
project. By opening a pull request you confirm that you have the right to
contribute the code under it.

## Code of conduct

[`CODE_OF_CONDUCT.md`](CODE_OF_CONDUCT.md).
