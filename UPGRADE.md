# UPGRADE

How to move from an older WSKLAD version to the current one, and how to undo a change
if it goes wrong.

Every release in the `0.x` line is **non-breaking by policy**: no existing hook name, no
hook argument count, no public class, method or option key is removed or changed. If a
release ever asks you to edit your code, that is a bug — report it.

---

## Upgrading to 0.12.0

### Nothing is required

There is no migration step. Deactivate the old version, upload the new one, activate it.
Your accounts, settings and logs are read as they were written.

What happens automatically on the first admin request:

| Step | What it does | If it fails |
|---|---|---|
| `UpdateWizard::init()` | Brings the database up to schema version 4 | Logs the error, shows an admin notice, retries on the next request |
| `Schema::install()` | Adds the five queue tables | Additive only — nothing is ever dropped |
| `Core::ensureSchema()` | Recreates tables if they went missing | Throttled to once a day |
| `Environment::protectDirectories()` | Writes `index.php` and `.htaccess` into the plugin's directories | Records the reason in `wsklad_directories_error` |

### Log files have moved

**This is the one change that affects your file system.**

Log files used to be written to:

```
wp-content/uploads/wsklad/logs/
wp-content/uploads/wsklad/accounts/{id}/logs/
```

They are now written to:

```
wp-content/wsklad/logs/
wp-content/wsklad/accounts/{id}/logs/
```

**Why.** `wp-content/uploads` is served by the web server as static files. The usual
protection is an `.htaccess` with `deny from all` — which works on Apache and does
nothing on nginx, and nginx is the more common production stack. A protection that
silently does nothing on 40% of hosts is worse than none, because it looks like it is
working. Files outside the served tree need the web server to cooperate at all.

**If you have an off-site log collector** (a backup job, a log aggregator, a
`tail -F` in cron) that points at the old path, update it. Nothing else is affected.

`Environment::get('wsklad_upload_directory')` still returns the old value: extensions
read it, and changing it in the `0.x` line would break them. The move of the *files* out
of it is what happened; the *key* is unchanged. The key is removed in `2.0.0`.

### Credentials are encrypted

`moysklad_password` and `moysklad_token` are now stored as an authenticated ciphertext
(`v1$…`, XChaCha20-Poly1305) instead of plain text.

**The migration is transparent and lazy.** Nothing to run, nothing to click:

- Reading a credential decrypts it. Existing plain-text rows are returned as they were.
- Writing a credential encrypts it. The first time an account is saved, it is stored
  encrypted.

So an account that has not been saved since the upgrade still has a plain-text row. It
is still encrypted in the sense that no *new* plain-text row is ever written. To convert
everything at once, open each account and press Save.

**Requirements.** The `ext-sodium` PHP extension. Without it the plugin keeps working —
it stores plain text as before — and shows a red admin notice saying so. It does not fail
silently, and it does not corrupt anything.

**If `wp_options` cannot be written**, the plugin shows a different red notice. That one
matters more: it means the installation key could not be stored, so **any credential
saved from that point will be unreadable on the next page load**. Fix the permissions or
free disk space before entering anything. The notice names the table and the reason.

**If you move the site to a new server, or restore a database from a different
installation**, the credentials will not decrypt. The encryption key is derived from
your `AUTH_KEY`/`SECURE_AUTH_KEY` plus a per-install salt stored in
`wsklad_install_salt`, hex-encoded. See the leak playbook below.

### If you upgraded from a build that stored the salt incorrectly

Early 0.12.0 development builds wrote the installation salt as raw binary into the
options table. A `utf8mb4` column rejects that, the write failed, and the key was
derived afresh on every request — so a credential saved on one page could not be read
back on the next. **If you ran one of those builds, the `wsklad_install_salt` option is
empty or unusable, and every credential you entered is gone.** There is no recovery: the
plaintext was never stored anywhere.

The fix is in the release. After updating, re-enter the credentials once on each
account. Going forward the salt is stored hex-encoded, the write result is checked, and
an admin notice appears if it cannot be stored.

### The account edit screen no longer shows your password

The password and token fields on the account screen are now always empty, and an empty
field means *keep the stored value*. Re-enter a credential only when you want to change
it.

This is not a convenience change. A `type="password"` field hides the value on screen
and does nothing about the HTML source, so the real credential used to be in the page
source of every account edit screen.

### New in this release, nothing to configure

- **Capabilities** — 8 new capabilities are declared and attached to the roles that
  already had `manage_options`. They are **not enforced**; every one currently behaves
  exactly as before. Enforcement lands in `1.0.0`.
- **Extension manifest** — extensions may now ship a `manifest.json`. The existing
  header-comment format keeps working unchanged. An incompatible extension is skipped
  with a visible reason instead of taking the site down.
- **Hook contract** — the ~100 hooks the plugin fires are frozen at contract version
  `1.0.0`. They will not change before `2.0.0`.
- **Queue tables** — `wsklad_queue`, `wsklad_queue_failed`, `wsklad_locks`,
  `wsklad_sync_state`, `wsklad_events`. Created empty; the sync engine lands later.

---

## Rolling back

### The plugin code

Deactivate 0.12.0 and reactivate 0.10.0. The account rows stay readable, because
`Account::getMoyskladPassword()` and `getMoyskladToken()` pass non-`v1$` values through
untouched — 0.10.0 wrote plain text, and 0.12.0 reads both forms.

What 0.10.0 cannot read is a `v1$` envelope: it has no decryption step, so an account
saved by 0.12.0 will authenticate with the literal string `v1$k1$…` as its password and
fail. **Before downgrading, open each account you have changed since upgrading and
re-enter the credential by hand.**

### The schema

Version 4 only *added* tables and indexes. Nothing was dropped, so rolling the code back
is safe. The five new tables are simply unused.

If you want them gone, drop them by hand:

```sql
DROP TABLE IF EXISTS wp_wsklad_queue;
DROP TABLE IF EXISTS wp_wsklad_queue_failed;
DROP TABLE IF EXISTS wp_wsklad_locks;
DROP TABLE IF EXISTS wp_wsklad_sync_state;
DROP TABLE IF EXISTS wp_wsklad_events;
```

Use your real `$table_prefix` — on multisite it is `base_prefix`, which is the *network*
prefix, not the per-blog one.

### Removing all data

Deactivating never deletes data. To delete everything, first set:

```php
add_option('wsklad_uninstall_remove_data', 'yes');
```

Then delete the plugin from the Plugins screen. Without that option, uninstalling
removes only the scheduled events and leaves your accounts, settings and log files in
place — a reinstall is then a one-click operation rather than a reconfiguration project.

Verify the result:

```sql
SHOW TABLES LIKE 'wp_wsklad%';   -- expect 0 rows
```

---

## Playbook: leaked credentials

Use this if a password or token may have been exposed — a log sent to support, a
compromised administrator account, a public directory listing that was open for a while.

1. **Rotate at the source first.** In Moy Sklad: *Settings → Integrations → Tokens*.
   Revoke the exposed token and issue a new one. Revoking is what makes the leak
   harmless; everything below is cleanup.

2. **Do not restore an old database backup over the top.** That puts the leaked
   credential back. If you must restore, redo step 1 afterwards.

3. **Change the encryption key** so any historical copy of the database becomes
   unreadable:

   ```php
   update_site_option('wsklad_key_id', 'k' . ((int) ltrim(get_site_option('wsklad_key_id', 'k1'), 'k') + 1));
   ```

   Then re-enter each account's credential and save it. Old rows are written with the
   old key id and stay readable; new rows use the new one.

4. **Purge the logs.** Logs are outside the web root, but they are still on disk:

   ```
   wp-content/wsklad/logs/
   wp-content/wsklad/accounts/*/logs/
   wp-content/uploads/wsklad/accounts/*/logs/     ← pre-0.10.1 location
   ```

   Delete the last path entirely. It should be empty after the upgrade; if it is not,
   something is still writing there.

5. **Delete old database backups** that predate step 3, or encrypt them. A backup taken
   after the leak is still a copy of the leak.

6. **Re-enable two-factor authentication** on the WordPress account that had access.

7. **Record it.** Time, blast radius, what you did, what happened. An incident with no
   record repeats.
