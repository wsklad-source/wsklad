<!--
    A short checklist, not a form to fill in. The questions below are the ones
    that have actually caused a pull request to be sent back.
-->

## What this changes

<!-- One or two sentences. If you cannot write this, the change is not ready. -->

Fixes #

## Checklist

- [ ] The base branch is `release/0.x`, and the branch name follows
      `type/description` (see `AGENTS.md`)
- [ ] `composer lint:php` passes
- [ ] `composer lint:cs` passes
- [ ] `composer hooks:validate` passes
- [ ] `composer test` passes, and the new behaviour has a test
- [ ] No credentials, tokens, `wp-config.php` values or personal data in the diff
- [ ] The plugin version in `wsklad.php` is **not** changed

## Tests

<!-- Required for a fix. A bug fix without a regression test will be asked for. -->

- [ ] The new test fails without this change
- [ ] The new test passes with it
- [ ] I did not relax an existing assertion to make the build green

## If this touches hooks

- [ ] Each hook name is fired from exactly one place
- [ ] The name is `wsklad_`-prefixed, lower snake case
- [ ] `docs/hooks.json` is updated in this same commit
- [ ] No existing hook name was changed or removed — that is a breaking change
      and belongs in a major version

## If this is a security fix

- [ ] This is **not** a public pull request
- [ ] See `SECURITY.md` for the private reporting channel

## Notes for the reviewer

<!-- Anything that would be faster to review if you said it here. -->
