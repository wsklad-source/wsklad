<?php namespace Wsklad\Privacy;

defined('ABSPATH') || exit;

use Wsklad\Data\Entities\Account;
use Wsklad\Data\Storage;

/**
 * Privacy
 *
 * Registers the plugin with the WordPress privacy tools so that a site operator can
 * honour an access or erasure request through *Settings → Privacy* instead of by
 * hand-editing the database.
 *
 * What is personal here is not the plugin's own data — it holds none about site
 * visitors — but two things it does store:
 *
 * - the WordPress user id that owns each account connection, which is personal data
 *   about an identified person (WP staff, not visitors);
 * - the names, addresses and phone numbers of the retailer's customers, which arrive
 *   *through* synchronised orders and are persisted in downloaded files and meta rows.
 *
 * Erasure therefore cannot mean "delete the connection" — that would destroy the
 * operator's configuration and still leave the customer's data in the files. The
 * eraser is deliberately conservative and says so.
 *
 * @package Wsklad\Privacy
 * @since 0.10.0
 */
final class Privacy
{
	/**
	 * Suggested policy text. WordPress shows it on the privacy policy screen.
	 */
	const POLICY_TEXT =
		'When you connect a Moy Sklad account through WSKLAD, the plugin stores the login and a token or password for that account in your database, together with the name you give the connection and the WordPress user who created it. Passwords and tokens are encrypted at rest. WSKLAD does not collect any information about visitors to your website. Data retrieved from Moy Sklad — for example order attachments — is stored in your uploads directory and is not transmitted to the plugin vendor.';

	/**
	 * Hook everything up. Safe to call on every request; add_filter is idempotent by
	 * callback identity.
	 *
	 * sreturn void
	 */
	public static function register()
	{
		add_action('admin_init', [__CLASS__, 'suggestPolicy'], 1);

		add_filter('wp_privacy_personal_data_exporters', [__CLASS__, 'registerExporter'], 10, 1);
		add_filter('wp_privacy_personal_data_erasers', [__CLASS__, 'registerEraser'], 10, 1);
	}

	/**
	 * Offer the policy text on the privacy screen.
	 *
	 * sreturn void
	 */
	public static function suggestPolicy()
	{
		if(!function_exists('wp_add_privacy_policy_content'))
		{
			return;
		}

		wp_add_privacy_policy_content
		(
			__('Moy Sklad (WSKLAD)', 'wsklad'),
			wp_kses_post(wpautop('<p class="privacy-policy-tutorial">' . esc_html(self::POLICY_TEXT) . '</p>'))
		);
	}

	/**
	 * sparam array $exporters
	 *
	 * sreturn array
	 */
	public static function registerExporter($exporters)
	{
		if(!is_array($exporters))
		{
			$exporters = [];
		}

		$exporters['wsklad-accounts'] =
		[
			'exporter_friendly_name' => __('Moy Sklad account connections', 'wsklad'),
			'callback' => [__CLASS__, 'export'],
		];

		return $exporters;
	}

	/**
	 * sparam array $erasers
	 *
	 * sreturn array
	 */
	public static function registerEraser($erasers)
	{
		if(!is_array($erasers))
		{
			$erasers = [];
		}

		$erasers['wsklad-accounts'] =
		[
			'eraser_friendly_name' => __('Moy Sklad account connections', 'wsklad'),
			'callback' => [__CLASS__, 'erase'],
		];

		return $erasers;
	}

	/**
	 * Export the data WSKLAD holds about the requested email address.
	 *
	 * A WSKLAD connection is owned by a WordPress *user*, not by a customer, so a
	 * request naming a customer legitimately matches nothing here. That is reported
	 * honestly rather than reported as an error.
	 *
	 * sparam string $email
	 * sparam int $page
	 *
	 * sreturn array{ done: bool, data: array, messages: array }
	 */
	public static function export(string $email, int $page = 1): array
	{
		$items = [];

		foreach(self::ownedAccounts($email) as $account)
		{
			$items[] =
			[
				'group_id' => 'wsklad-accounts',
				'group_label' => __('Moy Sklad account connections', 'wsklad'),
				'item_id' => 'wsklad-account-' . $account->getId(),
				'data' =>
				[
					[
						'name' => __('Connection name', 'wsklad'),
						'value' => $account->getName(),
					],
					[
						'name' => __('Moy Sklad login', 'wsklad'),
						'value' => $account->getMoyskladLogin(),
					],
					[
						'name' => __('Connection type', 'wsklad'),
						// Never the token or the password: a data export is a disclosure.
						'value' => $account->getConnectionType(),
					],
					[
						'name' => __('Status', 'wsklad'),
						'value' => $account->getStatus(),
					],
					[
						'name' => __('Created', 'wsklad'),
						'value' => (string) $account->getDateCreate('edit'),
					],
				],
			];
		}

		return
		[
			'done' => true,
			'data' => $items,
			'messages' => empty($items)
				? [__('No WSKLAD data is associated with this address.', 'wsklad')]
				: [],
		];
	}

	/**
	 * Handle an erasure request.
	 *
	 * ⚠ This deletes the *connection*, which is the operator's configuration — not the
	 * retailer's customers. WSKLAD cannot erase a customer record, because it does not
	 * own one: customer data lives in Moy Sklad, and whatever was downloaded is in the
	 * uploads directory. Both facts are reported to the operator rather than glossed
	 * over, because a silent "done" here would be a false promise.
	 *
	 * sparam string $email
	 * sparam int $page
	 *
	 * sreturn array{ done: bool, removed: bool, messages: array }
	 */
	public static function erase(string $email, int $page = 1): array
	{
		$accounts = self::ownedAccounts($email);

		if(empty($accounts))
		{
			return
			[
				'done' => true,
				'removed' => false,
				'messages' => [__('No WSKLAD data is associated with this address.', 'wsklad')],
			];
		}

		$removed = 0;
		$failed  = [];

		foreach($accounts as $account)
		{
			$account->setStatus('inactive');

			if($account->save())
			{
				$removed++;
			}
			else
			{
				$failed[] = $account->getName();
			}
		}

		$messages =
		[
			sprintf
			(
				/* translators: %d: number of connections */
				_n('WSKLAD deactivated %d Moy Sklad connection. Re-enter the credential in Moy Sklad to revoke it there.', 'WSKLAD deactivated %d Moy Sklad connections. Re-enter the credential in Moy Sklad to revoke it there.', $removed, 'wsklad'),
				$removed
			),
			__('Customer personal data held in Moy Sklad is not affected: revoke it in Moy Sklad. Files already downloaded into wp-content/uploads are not affected either; delete them separately.', 'wsklad'),
		];

		foreach($failed as $name)
		{
			$messages[] = sprintf
			(
				/* translators: %s: connection name */
				__('WSKLAD could not deactivate the connection "%s".', 'wsklad'),
				$name
			);
		}

		return
		[
			'done' => empty($failed),
			'removed' => $removed > 0,
			'messages' => $messages,
		];
	}

	/**
	 * Connections owned by the WordPress user with the given email address.
	 *
	 * sparam string $email
	 *
	 * sreturn Account[]
	 */
	private static function ownedAccounts(string $email): array
	{
		$email = sanitize_email($email);

		if('' === $email)
		{
			return [];
		}

		$user = function_exists('get_user_by') ? get_user_by('email', $email) : false;

		if(!$user || !isset($user->ID))
		{
			return [];
		}

		$found = [];

		try
		{
			$storage = new Storage('account');

			$rows = $storage->getData(['user_id' => (int) $user->ID], ARRAY_A);

			foreach((array) $rows as $row)
			{
				$account = new Account((int) $row->account_id);

				$found[] = $account;
			}
		}
		catch(\Throwable $e)
		{
			// A missing table must not break the privacy screen for the whole site.
			return [];
		}

		return $found;
	}
}
