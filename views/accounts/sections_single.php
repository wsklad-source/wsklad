<?php defined('ABSPATH') || exit;?>

<div class="row g-0">
    <div class="col-24">
        <div class="p-2 pt-3 pb-3">

            <a href="<?php echo wsklad()->admin()->utilityAdminAccountsGetUrl('dashboard', $_GET['account_id']); ?>" class="wp-heading-inline text-decoration-none fs-5"><?php _e('Панель', 'wsklad'); ?></a>

            >

            <span class="wp-heading-inline fs-5"><?php echo $args['name']; ?></span>

        </div>
    </div>
</div>

<?php do_action('wsklad_admin_before_accounts_sections_single_show'); ?>

<?php do_action('wsklad_admin_accounts_sections_single_show'); ?>

<?php do_action('wsklad_admin_after_accounts_sections_single_show'); ?>