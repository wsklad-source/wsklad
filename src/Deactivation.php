<?php namespace Wsklad;

defined('ABSPATH') || exit;

/**
 * Deactivation
 *
 * Deactivation stops the plugin's own scheduled work and unsubscribes its webhooks.
 * It must NOT delete data: a user deactivating to debug a conflict expects their
 * accounts to still be there when they reactivate.
 *
 * @package Wsklad
 */
final class Deactivation extends \Digiom\Woplucore\Deactivation
{
	public function __construct()
	{
		$this->clearSchedule();
		$this->unsubscribeWebhooks();
	}

	/**
	 * Remove every event this plugin registered with WP-Cron.
	 *
	 * @return void
	 */
	private function clearSchedule()
	{
		if(!function_exists('wp_clear_scheduled_hook'))
		{
			return;
		}

		$events =
		[
			'wsklad_cron',
			'wsklad_async_request',
			'wsklad_logs_cleanup',
			'wsklad_update',
		];

		foreach($events as $event)
		{
			wp_clear_scheduled_hook($event);

			if(function_exists('wp_unschedule_event'))
			{
				// Drop the remaining occurrences, not just the next one.
				while(wp_next_scheduled($event))
				{
					wp_unschedule_event(wp_next_scheduled($event));
				}
			}
		}
	}

	/**
	 * Tell Moy Sklad to stop delivering events while the plugin is off.
	 *
	 * Every outbound call that writes carries the `X-Lognex-WebHook-DisableByPrefix`
	 * header, which tells the Moy Sklad side to suppress the webhooks that write
	 * operations would otherwise generate. Without an active consumer those events pile
	 * up in the Moy Sklad log for nothing.
	 *
	 * Failures are swallowed on purpose: deactivation must complete even with no network.
	 *
	 * @return void
	 */
	private function unsubscribeWebhooks()
	{
		try
		{
			if(!class_exists('\Wsklad\Data\Storage'))
			{
				return;
			}

			$storage = new \Wsklad\Data\Storage('account');

			$accounts = $storage->getData(['status' => 'active'], ARRAY_A);

			foreach((array) $accounts as $row)
			{
				$account = new \Wsklad\Data\Entities\Account();

				$account->setId((int) $row->account_id);
				$account->setMoyskladLogin((string) $row->moysklad_login);
				$account->setMoyskladPassword((string) $row->moysklad_password);
				$account->setMoyskladToken((string) $row->moysklad_token);
				$account->setConnectionType((string) $row->connection_type);

				do_action('wsklad_deactivation_unsubscribe_webhooks', $account);
			}
		}
		catch(\Throwable $e)
		{
			// Nothing to do: the hooks an extension registered on this action are the
			// real unsubscribe mechanism, and their absence must not block deactivation.
		}
	}
}
