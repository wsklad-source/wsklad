<?php defined('ABSPATH') || exit;?>

<div class="row g-0">
    <div class="col-24">
        <div class="p-2 pt-3 pb-3">
            <a href="<?php echo wsklad()->admin()->utilityAdminAccountsGetUrl('dashboard', $_GET['account_id']); ?>" class="wp-heading-inline text-decoration-none fs-5"><?php _e('Панель', 'wsklad'); ?></a>
        </div>
    </div>
</div>

<div class="row g-0">
    <div class="col-24 col-lg-17">
        <div class="pe-0 pe-lg-2">
            <?php do_action('wsklad_admin_before_accounts_dashboard_show'); ?>

            <?php do_action('wsklad_admin_accounts_dashboard_show'); ?>

            <?php do_action('wsklad_admin_after_accounts_dashboard_show'); ?>
        </div>
    </div>
    <div class="col-24 col-lg-7">
        <?php do_action('wsklad_admin_accounts_dashboard_sidebar_show'); ?>
    </div>
</div>
