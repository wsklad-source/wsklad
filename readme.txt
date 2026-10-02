=== WSKLAD ===
Contributors: WSKLAD, Frescoref
Tags: moy sklad, мой склад, woocommerce, integration, erp
Requires at least: 5.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.10.0
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Donate link: https://wsklad.ru/market

Manage Moy Sklad accounts in WordPress: encrypted credentials, environment and database checks, detailed logs with secrets stripped.

== Description ==
The foundation a Moy Sklad integration is built on. WSKLAD holds your accounts and their credentials, verifies that the environment and the database are as they should be, and records what happened — leaving the actual data work to the extensions built on top of it.

= Key Features =
* **Any number of accounts** – Each Moy Sklad account carries its own credentials, connection settings and log level. Connect by permanent token or by login and password, and the plugin shows which mode an account is in, because Moy Sklad counts a login-and-password request as four units against your rate limit instead of one.
* **Credentials encrypted at rest** – Passwords and tokens are stored with XChaCha20-Poly1305, keyed from your WordPress salts plus a per-install salt. Accounts you connected before encryption existed keep working and are re-encrypted on their next save.
* **Diagnostics that name the actual fault** – Tools → Environments reports your server, PHP, WordPress and WooCommerce setup, and whether your database tables still match what this version expects: a dropped column, a column whose type changed, a missing index, or a metadata row left behind by a removed account.
* **Logs that keep your secrets** – Log files are written to `wp-content/wsklad`, outside `uploads`, and every entry is stripped of passwords and tokens as it is written — including by extensions that handle logging themselves. You choose the level and how many files to keep.
* **Extensibility** – Extensions add their own screens, tools and background work through documented hooks and filters. Two settings decide whether they are allowed to load at all, so a badly behaved extension can be stopped without touching the core.

Explore all features: [https://wsklad.ru/features](https://wsklad.ru/features)

== Translations ==
* English (Default)
* Russian (Built-in)

== Installation ==
1. Extract the archive and upload the `wsklad` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Open `Moy Sklad → Add accounts` and connect your first account.

Requires PHP 7.4 or newer and WordPress 5.3 or newer. The `sodium` PHP extension is strongly recommended: without it, credentials are stored unencrypted and the plugin says so on the settings screen rather than failing quietly. No configuration file and no command line — everything is done from the admin screens.

== Frequently Asked Questions ==

= Does this plugin import products, stocks or orders? =
Not by itself, and that is by design rather than an omission: the core contains no scheduler and no importer. It provides the accounts, credentials, diagnostics and logs that extensions need in order to do that work. Which is also why the core is worth installing on its own — an extension that fails has somewhere to report why.

= Why are my credentials stored in plain text? =
Because the `sodium` PHP extension is not available on your host. The plugin checks on every request and states it on the settings screen. Ask your host to enable `ext-sodium`; each account is encrypted the next time it is saved, and nothing needs doing to your data in the meantime.

= Where did my log files go? =
They moved out of `wp-content/uploads`. They used to be written to `uploads/wsklad/accounts/{id}/logs/`, which the web server served as static files to anyone who knew the path. They are now under `wp-content/wsklad/accounts/{id}/logs/`. If you collect logs from off-site, point that at the new path; nothing else is affected.

= An account screen says the database is wrong. What do I do? =
Open `Tools → Environments` and read the WSKLAD section: it names what is missing — a table, a column, an index — and what it found instead. Back up the database before changing anything. The plugin reports and never repairs, because a tool that quietly rewrites your tables is worse than one that tells you what is wrong.

= How do I remove everything the plugin has stored? =
Deactivate, then delete it from the Plugins screen and confirm. The uninstall screen offers the same choice and defaults to leaving your tables, options and files alone.

= Missing a feature? How can I add it? =
First check the extensions directory on the official site. If nothing fits, you can write your own against the plugin's hooks and filters, or ask the WSKLAD team for help. Either way, the core does not need to be modified, so your changes survive an update.

== Upgrade Notice ==

= 0.10.0 =
Nothing to do - no migration. Upload and activate. Log files moved from `wp-content/uploads` to
`wp-content/wsklad`: the old folder was readable by URL, so update any off-site log collector.
Moy Sklad credentials are now encrypted at rest.

== Screenshots ==

1. Accounts List — Empty State
2. Accounts List — One Account
3. Accounts List — Multiple Accounts
4. Adding an Account
5. Tools Dashboard
6. Settings
7. Extensions

== Changelog ==
A summary of major changes. The full history is kept in this file.

= 0.10.0 =
* Requirement: Minimum PHP version is now 7.4.
* Requirement: Minimum WordPress version is now 5.3.
* Tested: WordPress up to 7.1.
* Added: Credentials encrypted at rest with XChaCha20-Poly1305, keyed from the WordPress salts plus a per-install salt. Rows written before encryption keep working and are re-encrypted on the next write.
* Added: A notice on the settings screen when the `sodium` extension is missing, instead of storing credentials unencrypted without saying so.
* Added: Log files moved out of `wp-content/uploads`, where they were readable by direct URL.
* Added: Tables created on activation and self-healed if they disappear. Only the setup wizard created them before, so every other install path was left with no tables at all.
* Added: `uninstall.php`, soft by default, with an explicit opt-in for a full wipe and every dropped table verified afterwards.
* Added: Deactivation clears cron and webhooks. Both files were empty.
* Added: A privacy policy registered with the WordPress privacy tools, with an exporter and an eraser.
* Added: Log redaction as a pipeline processor, so an extension's own handler cannot bypass it.
* Added: `Schema::inspect()`, `composer schema:doctor` and the same report on Tools → Environments: they name a dropped column, a column whose type no longer matches, a missing index or a metadata row whose account is gone. Only a missing *table* was detectable before, so a table that existed and was wrong failed much later as a MySQL error in whatever the owner happened to be doing.
* Added: `security.txt` with the security contact and the supported-versions table.
* Added: Release gates on every push - PHP syntax on the oldest and the newest supported version, a coding standard split into rules that block and rules that advise, a check that the translation template matches the code, and six audits over the tree: cross-references, dangling symbols, version claims, wording, what the readme must satisfy, and what the release actually ships under `assets`.
* Added: A translation template generator with a CI gate that fails when the template drifts from the code, and a Russian catalogue covering every string in it.
* Fixed: A fatal on every request. Initialisation called a hook contract that does not exist in this release, so nothing after it ever ran.
* Fixed: The installation salt could not be stored, so no encrypted credential could be read back. It was written raw, the write failed, nothing checked it, and the key was derived afresh on every request.
* Fixed: A credential could be written to the log in the clear, because redaction was handed the encrypted value.
* Fixed: Schema self-heal never ran. It compared the version number only, so a dropped table was never recreated while reporting success.
* Fixed: The password and token no longer appear in the account screen HTML. An empty field now means "keep the stored value".
* Fixed: Missing CSRF nonces on the disconnect and verify actions, both of which were plain GETs.
* Fixed: SQL conditions prepared and every queried column whitelisted.
* Fixed: The options column no longer instantiates objects on read.
* Fixed: An unreadable stored date reported 1970-01-01 as if it were a real date.
* Fixed: Deleting an account no longer leaves its metadata behind.
* Fixed: Rotating the encryption key took effect only on the next request.
* Fixed: Per-status account counts collapse into a single grouped query, eight queries down to one.
* Fixed: Three missing views, an unclosed output buffer, a TypeError on a failure path, and dead code unreachable from any route.
* Fixed: The Interface settings tab rendered an empty form under a Save button that saved nothing. The tab is no longer offered, an old link to it explains itself instead of showing an empty box, and values stored by earlier versions are kept.
* Fixed: The `visible` flag on a settings section decided nothing. The check that draws a tab required the flag to be absent, so a section declared hidden was drawn anyway.
* Fixed: The token field is a password field, the screen warns that issuing a new token invalidates the previous one on every site still using it, the connection type defaults to token, and a login-and-password account warns that Moy Sklad counts each such request as four rate-limit units instead of one.
* Fixed: The account name in the admin header passed through a filter meant for markup and was handled as HTML.
* Fixed: An unknown column name was echoed into an admin notice unescaped, so a crafted column name could run markup in the admin. Both call sites are escaped.
* Fixed: Source map references in the stylesheet and in Bootstrap pointed at files the release does not ship, so the browser requested them on every admin page load and got a 404.
* Improved: About 94 KB less JavaScript blocks the first paint of an admin page. The three scripts load in the footer, with their library dependencies declared rather than held by registration order.
* Removed: The unminified Tocbot build, 45 KB that no page ever loaded, is no longer shipped.
* Removed: The in-plugin advertisement panel and its Logs tab. Log files have had their own screen in the extension area for some time.
* Updated: `readme.txt` rewritten to describe what the plugin does rather than what a plugin is said to do.

= 0.9.2 =
* Updated: Tocbot to 4.36.4.
* Added: composer.json to the release.
* Updated: readme.txt.
* Fixed: Deprecated functions and miscellaneous bugs.

= 0.9.1 =
* Updated: readme.txt.
* Fixed: Miscellaneous bugs and stability improvements.

= 0.9.0 =
* Updated: Bootstrap to 5.3.7.
* Updated: Translation files.
* Updated: readme.txt.
* Removed: Activation.
* Removed: Connection.
* Tested: WordPress up to 6.8.
* Fixed: Miscellaneous bugs and stability improvements.

= 0.8.0 =
* Updated: Translation files.
* Tested: WordPress up to 6.5.
* Fixed: Miscellaneous bugs and stability improvements.

= 0.7.0 =
* Updated: Composer to the latest version.
* Updated: Bootstrap to 5.3.3.
* Tested: WordPress up to 6.4.
* Fixed: The API host changed from `online.moysklad.ru` to `api.moysklad.ru`.
* Fixed: Miscellaneous bugs and stability improvements.

= 0.6.0 =
* Updated: Composer to the latest version.
* Updated: Bootstrap to 5.3.2.
* Updated: Translation files.
* Tested: WordPress up to 6.3.
* Fixed: Miscellaneous bugs and stability improvements.

= 0.5.2 =
* Fixed: Miscellaneous bugs and stability improvements.

= 0.5.1 =
* Updated: Composer to the latest version.
* Fixed: Miscellaneous bugs and stability improvements.

= 0.5.0 =
* Updated: Composer to the latest version.
* Updated: Bootstrap to 5.3.0.
* Updated: Translation files.
* Updated: WordPress.org assets.
* Fixed: Miscellaneous bugs and stability improvements.

= 0.4.1 =
* Fixed: Miscellaneous bugs and stability improvements.

= 0.4.0 =
* Added: Woplucore compatibility.
* Updated: Woplucore to the latest version.
* Updated: Translation files.
* Tested: WordPress up to 6.2.
* Fixed: Miscellaneous bugs and stability improvements.

= 0.3.4 =
* Fixed: Miscellaneous bugs and stability improvements.

= 0.3.3 =
* Updated: Translation files.
* Fixed: Miscellaneous bugs and stability improvements.

= 0.3.2 =
* Updated: Translation files.
* Fixed: Miscellaneous bugs and stability improvements.

= 0.3.1 =
* Fixed: Security issues.

= 0.3.0 =
* Added: Woplucore compatibility.
* Updated: Woplucore to the latest version.
* Fixed: Security issues and miscellaneous bugs.

= 0.2.0 =
* Updated: New admin interface.
* Fixed: Miscellaneous bugs and stability improvements.

= 0.1.1 =
* Fixed: Miscellaneous bugs and stability improvements.

= 0.1.0 =
* Initial release.
