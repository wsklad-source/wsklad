<?php defined('ABSPATH') || exit;?>

<div class="row">
	<div class="col pt-4 pb-2">
		<?php _e('Use the forms to delete MoySklad account from WooCommerce.', 'wsklad'); ?>
	</div>
</div>

<div class="">
	<?php do_action(WSKLAD_ADMIN_PREFIX . 'accounts_form_delete_show'); ?>
</div>
