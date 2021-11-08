<?php defined('ABSPATH') || exit; ?>

<h1 class="wp-heading-inline"><?php _e('MoySklad', 'wsklad'); ?></h1>

<a href="<?php echo wsklad_admin_accounts_get_url('create'); ?>" class="page-title-action">
    <?php _e('New account connection', 'wsklad'); ?>
</a>

<hr class="wp-header-end">

<?php wsklad_admin()->notices()->output(); ?>