<?php defined('ABSPATH') || exit;?>

<div class="bg-white p-2 pt-3 pb-3 mt-2 rounded-3">
	<?php
	printf('%s <b>%s</b>', __('ID of the account to be deleted:', 'wsklad'), $args['account']->getId());
	?>
	<br/>
	<?php
	printf('%s <b>%s</b>', __('Name of the account to be deleted:', 'wsklad'), $args['account']->getName());
	?>
	<br/>
	<?php
	printf('%s <b>%s</b>', __('Path of the account directory to be deleted:', 'wsklad'), $args['account']->getUploadDirectory());
	?>
</div>

<div class="">
	<?php do_action('wsklad_admin_accounts_form_delete_show'); ?>
</div>
