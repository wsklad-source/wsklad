<?php defined('ABSPATH') || exit;?>

<div class="row">
    <div class="col pt-2 pb-2">
		<?php _e('Use the forms to connect your MoySklad account to WordPress.', 'wsklad'); ?>
    </div>
</div>

<div class="">
    <?php do_action('wsklad_admin_accounts_form_create_show'); ?>
</div>
