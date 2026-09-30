<?php defined('ABSPATH') || exit;?>

<div class="bg-white p-2 pt-3 pb-3 mt-2 rounded-3">
	<?php
	printf('%s <b>%s</b>', esc_html__('Account ID:', 'wsklad'), esc_html($args['account']->getId()));
	?>
	<br/>
	<?php
	printf('%s <b>%s</b>', esc_html__('Account name:', 'wsklad'), esc_html($args['account']->getName()));
	?>
	<br/>
	<?php
	printf('%s <b>%s</b>', esc_html__('Account directory:', 'wsklad'), esc_html($args['account']->getUploadDirectory()));
	?>
</div>

<div class="">
	<?php do_action('wsklad_admin_accounts_form_delete_show'); ?>
</div>
