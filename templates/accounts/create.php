<?php defined('ABSPATH') || exit;?>

<div class="row">
    <div class="col pt-2 pb-2">
		<?php _e('Use of the forms to connect your MoySklad account to WooCommerce.', 'wsklad'); ?>
    </div>
</div>

<div class="">
    <?php do_action(WSKLAD_ADMIN_PREFIX . 'accounts_form_create_show'); ?>
</div>
