=== WSKLAD ===
Contributors: WSKLAD, Frescoref
Tags: мой склад, moy sklad, woocommerce, woo, warehouse
Requires at least: 5.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.10.0
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Donate link: https://wsklad.ru/market

Integration of WordPress and Moy Sklad (ERP/CRM)

== Description ==
Implementation of a mechanism for flexible exchange of various data between Moy Sklad and a site running WordPress. Implement the business logic you need with a robust core in the form of our plugin.

= Features =
* ✅ Flexible API.
* ✅ Big data support.
* ✅ Support for weak hosting.
* ✅ Maintaining event logs of various levels for timely response to problems.
* ✅ Expandability.

All sorted and linked features: [https://wsklad.ru/features](https://wsklad.ru/features)

== Translations ==
* English - default, always included
* Russian - always included

== Installation ==
1. Install from plugins or archive extract and upload folder "wsklad" to /wp-content/plugins (final path: /wp-content/plugins/wsklad).
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the plugin settings in the 'Moy Sklad' screen.

== Frequently Asked Questions ==

= Missing feature, how to add it? =
Try to implement the feature through actions and filters (extensibility mechanism). If this is not possible, you need to look at the extensions section on the official website. If there is no extension that adds the desired feature, you can develop it yourself or use paid services.

= Are updates being released? =
Updates are released as needed, but not more often than WordPress updates. To more or less guarantee timely updates, you can install the extension for services from the WSKLAD team. On average, updates are required once a month, when WordPress and WooCommerce updates are released.

== Upgrade Notice ==

= 0.10.0 =
Nothing is required. There is no migration step. Upload the new version and activate it;
your accounts, settings and logs are read as they were written, and the database is
brought up to date on the first admin request.

One change affects your file system: log files have moved out of `wp-content/uploads`.

They used to be written to `wp-content/uploads/wsklad/logs/` and
`wp-content/uploads/wsklad/accounts/{id}/logs/`, and they are now written to
`wp-content/wsklad/logs/` and `wp-content/wsklad/accounts/{id}/logs/`.

The old location was served by the web server as static files, so anyone who knew the
path could read them. The usual protection, an .htaccess with `deny from all`, works on
Apache and does nothing on nginx - and nginx is the more common production stack. A
protection that silently does nothing on some hosts is worse than none, because it looks
like it is working.

If you collect logs from off-site, update whatever pointed at the old path. Nothing else
is affected.

Moy Sklad credentials are now encrypted at rest. Existing accounts keep working
unchanged and are re-encrypted the next time they are saved. Without the `sodium` PHP
extension the plugin stores them unencrypted and says so on the admin screen rather than
failing quietly.

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
* Add: test barrier - PHPUnit config, WordPress stubs, contract tests, PHPCS split into
  blocking and advisory, composer scripts, and CI jobs for lint, unit, compat and
  integration.
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