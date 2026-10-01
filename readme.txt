=== WSKLAD ===
Contributors: WSKLAD, Frescoref
Tags: мой склад, moy sklad, woocommerce, woo, warehouse, интеграция, 1с, erp
Requires at least: 5.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.10.0
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Donate link: https://wsklad.ru/market

Accounts, diagnostics and logging for Moy Sklad in WordPress

== Description ==

WSKLAD is the core your Moy Sklad integration runs on. It holds your accounts, keeps their
credentials safe, tells you when something is wrong, and gets out of the way so add-ons can
do the actual data work.

**This plugin does not move products, stocks or orders by itself.** There is no scheduler
and no importer in it. What it does is everything those things need in order to work:

= Accounts =

* Connect any number of Moy Sklad accounts, each with its own credentials and its own log
  level.
* Two ways to authorise: a permanent token, or a login and password. The plugin tells you
  which is in use on each account, because from December 2026 Moy Sklad counts a
  login-and-password request as four units against your limit instead of one.
* Check a credential against the Moy Sklad API before you save it, so a typo is a message
  rather than a failed sync later.
* Disable an account without deleting it, so its history stays readable.

= Your data =

* Passwords and tokens are encrypted at rest with XChaCha20-Poly1305, keyed from your
  WordPress salts plus a per-install salt. If the PHP extension `sodium` is missing, the
  plugin says so on the settings screen instead of quietly storing them in the clear.
* Log files live in `wp-content/wsklad`, outside `uploads`. They were previously readable by
  direct URL by anyone who guessed the path.
* Secrets are stripped from log entries as they are written, including by extensions that
  handle logging themselves.
* Uninstalling removes your tables, options and files only if you ask it to. The default is
  to leave your data alone.

= When something breaks =

* **Tools → Environments** reports the server, PHP, WordPress and WooCommerce setup, and
  whether your database tables still match what this version expects: a dropped column, a
  column whose type changed, a missing index or a metadata row left behind by a removed
  account. A table that exists but is wrong is the failure a version check cannot see, and
  it used to surface much later as a database error in whatever you happened to be doing.
* The setup wizard checks the same things before you get started.

= For developers =

* Extensions add their own screens, tools and background work through documented hooks.
  Two settings decide whether they may load at all.

== Translations ==
* English - default, always included
* Russian - always included

== Installation ==

1. Upload the `wsklad` folder to `/wp-content/plugins/`, or install the archive from the
   Plugins screen.
2. Activate the plugin.
3. Open **Moy Sklad → Add accounts** and connect your first account.

Requirements, all checked on the setup screen:

* **PHP 7.4 or newer.** The `sodium` extension is strongly recommended: without it,
  credentials are stored unencrypted and the plugin tells you so.
* **WordPress 5.3 or newer.** Tested against 7.1.
* No configuration file and no command line. Everything is done from the admin screens.

== Frequently Asked Questions ==

= Does this plugin import products, stocks or orders? =

No, and it is not a missing feature - the core contains no scheduler and no importer. The
core manages accounts, credentials, diagnostics and logs; extensions built on it move the
data. This is why the plugin is useful on its own: an extension that fails has somewhere to
say why.

= Why are my credentials stored in plain text? =

Because the PHP extension `sodium` is not available. The plugin checks on every request and
says so on the settings screen. Ask your host to enable `ext-sodium`; each account is
encrypted the next time it is saved, and nothing has to be done to your data in the
meantime.

= Where did my log files go? =

They moved out of `wp-content/uploads`. They used to be written to
`uploads/wsklad/accounts/{id}/logs/`, which the web server handed out as static files to
anyone who knew the path. They are now under `wp-content/wsklad/accounts/{id}/logs/`. If you
collect logs from off-site, point that at the new path. Nothing else is affected.

= An account screen says the database is wrong. What now? =

Open **Tools → Environments**. The WSKLAD section names what is missing - a table, a column,
an index - and what it found instead. Back up the database first: the plugin reports and
never repairs, because a tool that quietly rewrites tables is worse than one that tells you.

= How do I remove everything the plugin stored? =

Deactivate, then delete it from the Plugins screen and confirm. Deleting through WordPress
asks first; the same choice is on the uninstall screen. If you never confirmed, your tables,
options and files stay where they are.

= Are updates released? =

Updates are released as needed, but not more often than WordPress updates. Most updates
follow a WordPress or WooCommerce release that changed something the plugin depends on.

⚠ WordPress.org accepts 300 characters in the notice below and silently truncates the rest, so
it is deliberately short and the detail lives in `== Changelog ==`. Anything written here counts
towards the limit, including comments, which is why this note is above the heading.

== Upgrade Notice ==

= 0.10.0 =
Nothing to do - no migration. Upload and activate. Log files moved from `wp-content/uploads` to
`wp-content/wsklad`: the old folder was readable by URL, so update any off-site log collector.
Moy Sklad credentials are now encrypted at rest.

== Screenshots ==

1. Panel empty
2. Panel one
3. Panel multiple
4. Add accounts
5. Tools
6. Settings
7. Extensions

== Changelog ==

= 0.10.0 =
* Add: credentials encrypted at rest (XChaCha20-Poly1305, key derived from the WordPress
  salts plus a per-install salt). Existing plain-text rows keep working and are
  re-encrypted on the next write. Without ext-sodium the plugin says so on the admin
  screen instead of failing quietly.
* Add: log files moved out of wp-content/uploads, where they were readable by direct URL.
* Add: tables are created on activation and self-heal if they disappear. Before this only
  walking through the setup wizard created them, so any other install path left the site
  with no tables at all.
* Add: uninstall.php, soft by default, with an explicit opt-in for a full wipe, and every
  dropped table verified afterwards.
* Add: deactivation clears cron and webhooks. Both files were empty.
* Add: privacy policy, registered with the WordPress privacy tools, plus an exporter and
  an eraser.
* Add: log redaction as a pipeline processor, so an extension's own handler cannot
  bypass it.
* Add: a release gate set that runs on every push - PHP syntax on the oldest and the
  newest version the plugin supports, a coding standard split into rules that block and
  rules that advise, a check that the translation template matches the code, and six
  audits over the tree: cross-references, dangling symbols, version claims, wording,
  what WordPress.org requires of the readme, and what the release actually ships under
  assets.
* Add: security.txt, which answers the standard security contact and supported-versions
  requests.
* Add: a translation template generator with a CI gate that fails when the template
  drifts from the code, and a Russian catalogue covering every string in it.
* Fix: the plugin fataled on every request. Initialisation called a hook contract that
  does not exist in this release, so nothing after it ever ran.
* Fix: the installation salt could not be stored, so no encrypted credential could ever be
  read back. It was written raw, the write failed, nothing checked, and the key was
  derived afresh on every request.
* Fix: a credential could be written to the log in the clear. Redaction was handed the
  encrypted value, which the redactor ignores on purpose.
* Fix: schema self-heal never ran. It checked the version number only, so a dropped table
  was never recreated, and it reported success while doing nothing.
* Fix: password and token no longer appear in the account screen HTML. An empty field now
  means "keep the stored value".
* Fix: CSRF nonce added to the disconnect and verify actions. Both were plain GETs.
* Fix: SQL conditions prepared, and every queried column whitelisted.
* Fix: the options column no longer instantiates objects on read.
* Fix: an unreadable stored date reported 1970-01-01 as if it were real.
* Fix: deleting an account no longer leaves its metadata behind.
* Fix: rotating the encryption key took effect only on the next request.
* Fix: per-status account counts collapse into a single grouped query.
* Fix: three missing views, an unclosed output buffer, a TypeError on a failure path, and
  dead code that was unreachable from any route.
* Fix: the Interface settings tab rendered an empty form under a Save button that saved
  nothing. The tab is no longer offered, and an old link to it now explains itself instead
  of showing an empty box. Values stored by earlier versions are kept.
* Fix: "visible" on a settings section decided nothing. The check that draws a tab
  required the flag to be absent, so a section declared hidden was drawn anyway.
* Fix: the token field is a password field, the screen warns that issuing a new token
  invalidates the previous one on every site still using it, connection type defaults to
  token, and a login-and-password account warns that Moy Sklad counts each request as 4
  units of the rate limit instead of 1.
* Fix: the account name in the admin header went through a filter meant for markup, so it
  was handled as HTML rather than as text.
* Fix: an unknown column name was echoed into an admin notice unescaped, so a crafted column
  name could run markup in the admin. It is escaped, and both call sites are covered.
* Add: `Schema::inspect()` and `composer schema:doctor` report what the live database is
  missing - a dropped column, a column whose type no longer matches, a missing index, or a
  metadata row whose account is gone. Until now only a missing *table* was detectable, so a
  table that was present and wrong failed later as a MySQL error in whatever the user
  happened to be doing. The same report is on the Tools > Environments screen.
* Remove: the in-plugin advertisement panel and its Logs tab. Log files have had their own
  screen in the extension area for some time, and the panel was all the main plugin had to
  show on that page.
* Up: the admin page loads about 94 KB less JavaScript before it can paint. Bootstrap,
  Tocbot and the plugin's own script were all being fetched in the page header, where they
  block rendering; they now load at the end of the document. Tocbot's unminified build -
  45 KB that no page ever asked for - is no longer shipped, and the stylesheet and
  Bootstrap no longer point at source map files that are not in the package, which had
  been producing a 404 in the browser console on every admin page.
* WP tested up to: 7.1
* WP requires at least: 5.3
* Requires PHP: 7.4

= 0.9.2 =
* Up: readme.txt
* Up: Tocbot to 4.36.4
* Add: composer.json in release.
* Fix: deprecated functions.
* Fix: more.

= 0.9.1 =
* Up: readme.txt
* Fix: more.

= 0.9.0 =
* Up: Bootstrap to 5.3.7
* WP tested up to: 6.8
* Remove activation.
* Remove connection.
* Up: language files.
* Up: readme.txt
* Fix: more.

= 0.8.0 =
* Up: language files.
* WP tested up to: 6.5
* Fix: more.

= 0.7.0 =
* Fix: replace online.moysklad.ru to api.moysklad.ru
* WP tested up to: 6.4
* Up: composer to latest.
* Up: Bootstrap to 5.3.3
* Fix: more.

= 0.6.0 =
* Up: language files.
* Up: composer to latest.
* WP tested up to: 6.3
* Up: Bootstrap to 5.3.2
* Fix: more.

= 0.5.2 =
* Fix: more.

= 0.5.1 =
* Up: composer to latest.
* Fix: more.

= 0.5.0 =
* Up: WP.org assets.
* Up: Bootstrap to 5.3.0.
* Up: language files.
* Up: composer to latest.
* Fix: more.

= 0.4.1 =
* Fix: more.

= 0.4.0 =
* WP tested up to: 6.2
* Ability: use Woplucore.
* Up: Woplucore to latest
* Up: language files.
* Fix: more.

= 0.3.4 =
* Fix: more.

= 0.3.3 =
* Up: language files.
* Fix: more.

= 0.3.2 =
* Up: language files.
* Fix: more.

= 0.3.1 =
* Fix: security.

= 0.3.0 =
* Ability: use Woplucore.
* Up: Woplucore to latest
* Fix: security and more.

= 0.2.0 =
* New interface.
* Fix: more.

= 0.1.1 =
* Fix: more.

= 0.1.0 =
* Init release.