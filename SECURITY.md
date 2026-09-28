# Security policy

## Reporting a vulnerability

**Email `support@wsklad.ru`. Do not open a public issue.**

A public issue is a working reproduction with a permanent, indexed URL. If the
report is valid, that URL is a working exploit until a fix has shipped to
WordPress.org and users have updated — which is days, not minutes. A public issue
also means a third party reads your report before the maintainer does, and the
first thing they write is usually a working proof of concept.

Put "SECURITY" in the subject line. Write in Russian or English; both are fine.

Please include:

- the WSKLAD version (`wp-content/plugins/wsklad/wsklad.php`), WordPress version, PHP version
- what an attacker can do, not only what they can see
- the steps to reproduce, ideally as a request or a short script
- whether any real credential or personal data is involved

Please do **not** include:

- live credentials — a Moy Sklad token above all. If one has been exposed, revoke
  it in Moy Sklad first, then tell us; a revoked token is still useful to us
- a customer's personal data. A synthetic reproduction is worth more than a real
  one, and it spares your customer an incident
- a working exploit against a third party's live site. We will help you take it
  down; we will not run it

## Response time

**Five working days for the first reply.**

That is slower than the 72 hours most policies promise, and it is a deliberate
choice. This is a one-maintainer project with roughly one day a week of working
time. A 72-hour promise from a project of that size is a promise that is broken
on the first weekend, and a broken SLA is worse than an honest one: it teaches
reporters that the channel is not monitored, which is exactly the assumption that
makes an unpatched vulnerability dangerous.

What five working days buys you:

| | |
| --- | --- |
| First reply | Within 5 working days. Always a human, never an autoresponder |
| Triage | Acknowledged, assessed, and classified within the reply |
| Fix target | Agreed with you individually, once the scope is known |
| Advisory | Published after the fix is on WordPress.org, with credit unless you ask otherwise |

If your report is high severity — remote code execution, authentication bypass,
exposure of stored credentials — say so in the subject line and it jumps the
queue. A validated critical issue gets a fix before the reply is written.

## What counts as a security issue

In scope:

- Anything that lets an unauthenticated visitor read or change data
- SQL injection, cross-site scripting, CSRF, object injection, file inclusion
  or path traversal, remote code execution, server-side request forgery
- Exposure of stored credentials: a Moy Sklad password or token, a WordPress
  salt, a `wp-config.php` value
- Unauthenticated access to files the plugin wrote to disk, including logs and
  anything downloaded from an external API
- A privilege escalation between WordPress roles, or the absence of an
  authorisation check on an admin action
- A dependency with a known CVE that the plugin actually calls
- A cryptographic defect: an IV or nonce reused under one key, a MAC that is not
  verified before use, a silent fallback to a weaker algorithm

Out of scope:

- A missing capability check on a page that renders nothing sensitive. Report it
  anyway; the judgement about severity is ours, not yours
- Denial of service from a large legitimate request
- Anything requiring an administrator to install a second malicious plugin
  deliberately
- SQL injection reachable only by someone who can already write to the database
- The plugin being outdated, or a version with a known fix not yet applied
- Style, structure, or a missing docblock
- A finding produced by a scanner with no path to a real attacker. Automated
  scanners are welcome — send the output and we will triage it — but a raw report
  with no demonstrated impact will be closed as "not a vulnerability", with the
  reason given

## Known limitations, stated up front

These are in the code today and are documented here rather than hidden, because a
policy that only lists fixed issues is not a security policy.

- **The extension surface is the plugin's public API.** Roughly 90 hooks exist and
  a hook that fires twice fires twice for every subscriber. This is tracked in
  `docs/hooks.json` and asserted by `tests/Contract/HooksTest.php`.
- **State-changing admin handlers are not uniformly nonce-checked.** A scan of
  `src/` is in `tests/Contract/NoncesTest.php`; read the current result before
  relying on CSRF protection for a specific screen.
- **`/tests`, `/.github`, `/tools` and `/plans` are excluded from the release**
  via `.distignore`, but `vendor/` is shipped deliberately, because WordPress.org
  installs never run `composer install`. The dependency tree is therefore part of
  the attack surface. `composer.lock` is shipped for exactly this reason.
- **Five runtime dependencies are pinned to `dev-*` branches** rather than to
  tags. There is no version to audit and no way to reproduce a build from a year
  ago. Tracked in `plans/00-AUDIT.md` and `plans/80-PLAN-SECURITY-COMPLIANCE.md`.

## Cryptography

Stored Moy Sklad credentials are encrypted with XChaCha20-Poly1305
(`ext-sodium`). The key is derived per installation with HKDF-SHA256 over the
WordPress salts, a per-install network option, and — on multisite — the blog id.

If `ext-sodium` is unavailable, the plugin does not substitute a weaker cipher.
It stores the value unchanged and tells the administrator. That is a deliberate
trade: plain storage on a host without sodium is bad, but a silent downgrade to
something reversible-looking is worse, because it is indistinguishable from
working encryption in the UI.

## Disclosure

Fixes ship as a tagged release, then as a WordPress.org release, then as an
advisory in `CHANGELOG.md` with credit to the reporter unless anonymity is
requested. There is no CVE process here; with one maintainer, the advisory is the
process.

## Supported versions

See [`SUPPORTED_VERSIONS.md`](SUPPORTED_VERSIONS.md). In short: the current
`0.x` line only.
