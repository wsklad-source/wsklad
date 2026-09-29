<?php namespace Wsklad\Admin\Wizards;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;

/**
 * UpdateWizard
 *
 * Runs the schema migration when the stored schema version is behind the code.
 *
 * Until 0.10.0 `init()` was an empty `// TODO`, so a plugin update that changed the
 * DDL left the site silently on the old schema. The wizard is still the right place for
 * a multi-step migration; this one step is small enough to run directly, and the
 * upgrade notice is the part a user actually notices.
 *
 * @package Wsklad\Admin\Wizards
 * @since 0.10.0
 */
final class UpdateWizard extends WizardAbstract
{
	use SingletonTrait;

	/**
	 * Option holding the timestamp of the last time the update check ran.
	 */
	const CHECKED_OPTION = 'wsklad_update_checked';

	/**
	 * UpdateWizard constructor.
	 */
	public function __construct()
	{
		$this->setId('update');
	}

	/**
	 * Check whether the schema needs an upgrade and, if so, do it.
	 *
	 * @return bool True when an upgrade was applied
	 */
	public function init()
	{
		$schema = wsklad()->schema();

		if($schema->isCurrent() && $schema->tablesExist())
		{
			update_option(self::CHECKED_OPTION, time(), false);

			return false;
		}

		$from = $schema->getVersion();

		$applied = $schema->install();

		update_option(self::CHECKED_OPTION, time(), false);

		if($applied)
		{
			$this->notice($from);
		}

		return $applied;
	}

	/**
	 * Tell the administrator the schema was migrated.
	 *
	 * @param int $from
	 *
	 * @return void
	 */
	private function notice(int $from)
	{
		wsklad()->admin()->notices()->create
		(
			[
				'id' => 'wsklad_schema_updated',
				'dismissible' => false,
				'type' => 'success',
				'data' => sprintf
				(
					/* translators: 1: previous schema version, 2: new schema version */
					__('WSKLAD database was updated from version %1$s to %2$s.', 'wsklad'),
					$from,
					wsklad()->schema()->getVersion()
				),
			]
		);
	}
}
