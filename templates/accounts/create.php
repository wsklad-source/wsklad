<?php defined('ABSPATH') || exit;?>

<div class="row">
    <div class="col pt-4 pb-3">
		<?php _e('Use one of the forms to connect your MoySklad account to WooCommerce.', 'wsklad'); ?>
    </div>
</div>

<div class="g-grid">
    <div class="">
        <?php do_action(WSKLAD_ADMIN_PREFIX . 'accounts_form_create_by_account_show'); ?>
    </div>
    <div class="">
        <?php do_action(WSKLAD_ADMIN_PREFIX . 'accounts_form_create_by_token_show'); ?>
    </div>
</div>
