# Changelog

All notable changes to WSKLAD are recorded here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and
the project uses [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## About this file

Two different sources feed this file, and mixing them silently is how a changelog
starts lying, so they are separated:

- **`0.10.0` and earlier were reconstructed from `git log`.** Two consequences worth
  stating plainly: the dates are *commit* dates, not release dates, and roughly two
  thirds of that history is a single message `Fix: more` repeated. Those entries are
  described by what the commits touched, not by what they claimed.
- **`0.10.0` onwards are written from the release plan and from the work itself.** The
  milestones are the ones in `plans/RELEASES.md`. Where a planned item was not done,
  the section says so instead of quietly dropping it.

Going forward, a commit that changes behaviour is expected to add a line here in the
same commit. That is the rule that makes the next reconstruction unnecessary.

### Why this is 0.10.0 and not 0.10.1

Two commits already set the version string to `0.10.0` (`2eb6ee0`, `54607fe`) and the
readme was updated to match, but **no tag was ever created** — not `0.10.0`, not
`v0.10.0`. Per the versioning policy a tag cannot be created after the fact for a build
that was already described as 0.10.0, and this release *is* that build: the security,
correctness and test-barrier work was the rest of what 0.10.0 was going to be. So it
ships as `v0.10.0`, in the new `vX.Y.Z` form, and the two earlier commits are folded into
this changelog section rather than listed as a release of their own.

The release plan had staged the `0.10.x` line as four milestones. They were implemented as
one, and the numbering is deliberate rather than tidy:

| Planned | Cut as | Why |
|---|---|---|
| 0.10.1 «Безопасность данных» | `0.10.0` | folded in |
| 0.10.2 «Корректность» | `0.10.0` | folded in |
| 0.10.3 «Устойчивость API» | **never** | blocked, see below |
| 0.10.4 «Тестовый барьер» | `0.10.0` | folded in |

`0.10.3` was never cut and nothing pretends otherwise. It needed the
`frescoref/moysklad` library to move the API protocol out of the plugin, and that library
does not exist — `F:\Devs\Frescoref\moysklad` contains a `composer.json` and no `src/`. A
`0.x` library cannot go into a plugin that users install anyway (RISKS P-13), so there was
no honest way to ship it. The two pieces of it that did not depend on the library are
planned for a later release; `ErrorLocalizer` is still outstanding.

## [0.10.0]

Security, correctness and the test barrier, as one release. Schema version 3. The extension
contract and the architecture foundation are **not** here — those are later milestones, and
a `0.10.x` tree contains no trace of either, which the verification below asserts.

### Added

- **`Cryptography`**: `SodiumCryptography`, `UnavailableCryptography`, `KeyProvider`.
  XChaCha20-Poly1305, key derived via HKDF from the WordPress salts plus a per-install
  salt. Transparent and lazy: existing plain-text rows read unchanged and are re-encrypted
  on the next write. Without `ext-sodium` the plugin degrades to plain text *loudly*,
  with an admin notice — it does not fail silently and does not corrupt anything.
- **`Schema`** — extracted from the setup wizard, which was the only thing creating the
  tables. `install()` now runs on activation and self-heals, so an install path that
  skipped the wizard no longer leaves the site with no tables. `install()` is idempotent:
  dbDelta is additive and the gate is `isCurrent() && nothingMissing()`.
- **`uninstall.php`** and a root `Uninstall`: soft by default, with an explicit opt-in for
  a full wipe, so the Plugins screen actually cleans up. Every table it drops is verified
  afterwards, and a failure is reported through `wsklad_uninstall_incomplete` rather than
  letting "uninstalled" imply otherwise.
- **`Deactivation`** clears cron and webhooks. It was an empty file.
- **`UpdateWizard::init()`** implemented. It was an empty `// TODO`, so a DDL change
  shipped in a plugin update was silently never applied.
- **`privacy-policy.txt`**, registration with the WordPress privacy tools, and an
  exporter/eraser pair.
- **`Redactor`**, in the logging pipeline. A Monolog *processor*, not a formatter,
  because extensions can push their own handler and a formatter-level redactor would be
  bypassed by all of them.
- **Test barrier**: PHPUnit config, a hand-written WordPress stub layer, contract tests
  for views/nonces/security primitives, `phpcs.xml.dist` split into blocking and advisory,
  `composer` scripts for every gate, and CI jobs `lint-php`, `lint-cs`, `lint-hooks`,
  `test-integration`, `test-unit`, `test-compat`. Actions pinned to commit SHAs.
  Dependabot.
- Playbook «утечка секретов» in `plans/RUNBOOK.md`.
- `composer.lock` removed from `.distignore` — dependency auditability.
- `LICENSE`, `CONTRIBUTING.md`, `SECURITY.md`, `SUPPORTED_VERSIONS.md`,
  `CODE_OF_CONDUCT.md`, `README.md`.

### Security

- **Log files no longer live under `wp-content/uploads`.** They were reachable by direct
  URL. The usual fix — an `.htaccess` with `deny from all` — works on Apache and does
  nothing on nginx, and nginx is the more common production stack. A protection that
  silently does nothing on 40 % of hosts is worse than none, because it looks like it is
  working. The logs moved to `wp-content/wsklad/`.
- **Passwords and tokens no longer appear in the account screen HTML.** They were
  rendered into the page source of every account edit screen; `type="password"` hides the
  value on screen and does nothing about the DOM. An empty field now means "keep the
  stored value" — the obvious fix would otherwise have destroyed credentials on every
  unrelated save.
- **CSRF nonces** on the disconnect and verify actions. Both were plain GETs, so any
  off-site image tag was enough to trash a shop's accounts or trigger a Moy Sklad request
  as a logged-in administrator.
- **SQL injection closed.** `parseQueryConditions()` interpolated both the value and the
  column name; the column name had no sanitising branch at all, so it was the easier of
  the two. `orderby` came from `$_REQUEST` through `sanitize_text_field()`, which strips
  tags but happily passes commas and parentheses into `ORDER BY`. Both are now prepared
  and whitelisted.
- **The `options` column no longer instantiates objects on read.** `maybe_unserialize()`
  was called without `allowed_classes`, so any writer of that column could reach object
  instantiation through a POP chain the moment the row was read.
- `print_r` of a whole account row removed from a table cell; `$item['name']` escaped.

### Fixed

- ⚠ **The installation salt could not be stored, so no credential could ever be read
  back.** `random_bytes()` output is not valid UTF-8, and the salt was written raw into an
  option. The write failed, `update_site_option()` returned false, nothing checked the
  return value, and the salt read back empty — so the key was derived afresh on every
  call. A credential could be saved and never read again, silently, forever. The salt is
  hex-encoded for storage now, the write result is checked, a failed write raises an admin
  notice, and `wsklad_install_salt_not_persisted` fires.
  **Every unit test for the cryptography passed while this was broken.** A stubbed
  `update_site_option()` is a PHP array; a real one is a `utf8mb4` column that rejects
  binary. Only a real database showed it.
- ⚠ **`Schema::install()` gated on the version number alone, so the self-heal never
  worked.** A dropped table leaves the version option untouched; `install()` returned
  early, `dbDelta()` never ran, and `ensureSchema()` reported success while doing nothing.
  Silent, permanent, and self-reporting as healthy.
- ⚠ **A credential could be written to the log in the clear.** The account getters
  registered the *raw column* with the redactor and decrypted afterwards. The raw column
  is a `v1$…` envelope, and `Redactor::addKnownValue()` refuses any value containing `$`
  — by design, to skip envelopes. So with encryption on, which is the only configuration
  that matters, **nothing was registered at all**, and a secret logged under a key that
  gives nothing away (`request_id`, an exception context, a stack trace) went out
  verbatim. Registration now happens on the decrypted value, and `addKnownSecret()` exists
  because a real password may legitimately contain `$`. The redaction test had passed
  throughout: it registered its own secret by hand and never exercised the path.
- **Deleting an account orphaned its meta rows.** The account row went, the
  `wsklad_accounts_meta` rows stayed, and nothing reported it — and a recreated account
  with the same id would have inherited the old metadata.
- **A key rotation had no effect until the next request.** `Core::cryptography()` built
  its own `KeyProvider` while `Core::keyProvider()` held another, so rotating reset the
  cipher cache on a different object. Both now share one provider, and
  `Core::rotateKeyId()` is the single entry point.
- **An unreadable stored date read as 1970-01-01.** `strtotime()` returns `false` for what
  it cannot read, and `false` in an `: int` return becomes `0`. A row damaged by a bad
  migration would display a 56-year-old date as real — with no exception, no log line, no
  failed assertion. Unreadable is now `null` and is announced through
  `wsklad_unparseable_date`; an empty column stays quiet.
- `deleteMeta()` declared `: array` and returned `false` — a `TypeError` on the failure path.
- `Core::admin()` called `ob_start()` on every invocation and never closed it.
- `readExtraData()` read `get_post_meta()` with an account id, which is a different id
  space from `wp_posts.ID`, and built setter names (`set_<key>`) that match no setter on
  `Account`. Removed rather than guessed at.
- `Schema::tablesExist()` read an undefined variable, so the self-heal path could never
  have run at all.

### Changed

- **The per-status account counts collapse into a single grouped query.** The list screen
  called `countBy()` once per status, so six statuses meant six round trips before a
  single row was rendered. The total query count of the screen is deliberately *not*
  claimed — nothing measures it, and an unmeasured number in a changelog rots.
- Three missing view files, so error paths stopped being fatal.
- `Admin\Accounts\Create` (140 lines, unreachable from any route) and `Traits\Sections`
  (a duplicate of `SectionsTrait`) removed. Three dead settings removed from the UI, with
  **the stored values preserved on write** so an existing installation loses nothing.
- `readme.txt` states a real `Tested up to`, and CI's WordPress-version gap is called out
  rather than papered over.

### Known limitations

Stated plainly, because each one is a promise the release does not yet keep:

- **`composer.lock` is out of sync with the new `require-dev` block**, so every CI job
  fails at the install step. It was deliberately not regenerated: the configured Composer
  mirror rewrites `dist.url` across all packages, and a lock rewritten that way points CI
  at a third-party mirror instead of GitHub. That is a worse outcome than a red job.
- **PHPUnit cannot be run on this machine** — `vendor/bin/phpunit` is absent and
  installing it would mean the rewrite above. The gates that do run are `php -l` over 156
  files on PHP 8.4 and 7.4, PHPCS, and a real-WordPress boot probe.
- **`wsklad-ru_RU.po` is behind the regenerated `.pot`** and needs `msgmerge`, which is not
  installed here.
- **CI is PHP-only**: it runs no WordPress version, so the `Tested up to` header is
  backed by the local integration install, not by CI.
- No business logic beyond accounts. The API protocol, the extension contract and the
  architecture foundation are all later milestones, and none of them is in this tree.

## [0.10.0] — 2026-03-13

No git tag exists for this version. Two commits set the string
(`2eb6ee0`, `54607fe`), which is itself worth noting.

### Added

- Blueprints: `5b69f28`, with fixes in `9e4ca3c` and `d3ba0b1`.
- `digiom/woplucore` dev-main dependency.

### Changed

- Frontend assets: Bootstrap 5.3.7 (`59855a0`), Tocbot 4.36.4 (`b4f7fce`),
  corresponding SCSS compiled to CSS.
- Bootstrap JS moved out of the plugin root into `assets/js/bootstrap/`
  (`97557a8`), and its licence file added to the distribution (`69e1248`) —
  previously the bundled Bootstrap shipped without one.
- `get_terms()` replaced after deprecation (`13d6dd8`).
- Escaping and i18n pass across 40 files: `9aa2c27` alone touches 39 templates and
  5 classes, and reads as a systematic correction rather than a feature.
- Account list, dashboard, delete and verification screens reworked across
  `391f989`, `d9a3af0`, `5b69f28` and the surrounding `Fix: more` commits —
  30 commits in this section, of which 24 carry only the message `Fix: more`.

### Removed

- `8a022f1` "Remove connection", `a3f7674` "Remove activation": the legacy
  connection code path and the old activation hook.

### Notes

- `readme.txt` and language files updated repeatedly (`bac8fa5`, `5e4a6a1`,
  `34516b3`, `79a0bef`, `90b4182`).
- `2650144` added `composer.json` to the release by removing it from
  `.distignore`. This change is reverted in the current `.distignore` policy, along
  with `composer.lock` and the development files — see `.distignore`.

### Security

- `frescoref/woplucore` was added as a runtime dependency, replacing the single
  `digiom` framework namespace.
- Hook surface was inventoried: 93 distinct names across 95 firing sites, recorded in
  `docs/hooks.json`. Nothing in that inventory was a *fix* — it is the baseline the
  0.11.0 contract was measured against.

## [0.9.2] — 2025-07-24

`d4547d7`. Precedes the section above in the log but is listed here for
completeness; the version numbers in the repository do not follow the commit
dates, which is a known problem with this history and is the reason this file
notes dates so carefully.

The API host change from `online.moysklad.ru` to `api.moysklad.ru` (`8f6a254`)
predates this release by eighteen months and is listed under 0.10.0 only because
it appears in the log there; it is not part of this release.

## Earlier releases

Tags `0.1.0` … `0.9.1` exist in the repository without the `v` prefix, along with
`0.2.0-pre` and `0.2.0-pre2`. Per the project's versioning policy, published tags
are never moved and never recreated, so they are left as they are. New tags use
the `vX.Y.Z` form.

No changelog entries are reconstructed for them. Doing so from commit messages
alone would produce a longer, more confident and less accurate document than the
one it replaced.

## How this was verified

`0.10.0` is not "the code looked right". The tree was built from `release/0.x` and checked
against a real WordPress 7.1 install with a real MySQL 8.4 database, with the schema
dropped first so nothing could mask a missing table:

- 29 assertions, all green: the plugin boots, `Schema::install()` creates **exactly** the
  two tables this release owns, `Schema::VERSION` is 3 and matches the stored option, the
  salt is 32 bytes and survives the database, a credential round-trips encrypted, and the
  plaintext is redacted under a harmless key.
- The same run asserts that **no 0.11 or 0.12 symbol is present** — 16 classes and 6
  `Schema` methods that arrive later are all confirmed absent. A `0.10.x` release that
  quietly contained the queue tables and the extension contract would be a version number
  nobody could reason about afterwards.
- `php -l` over 156 files on PHP 8.4 and 7.4, and PHPCS blocking, both clean.
