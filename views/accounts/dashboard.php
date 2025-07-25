<?php defined('ABSPATH') || exit;?>

<?php
    $account_id = 0;
    if(!empty($_GET['account_id']))
    {
        $account_id = sanitize_text_field(wp_unslash($_GET['account_id']));
    }
?>

<div class="row g-0">
    <div class="col-24">
        <div class="p-2 pt-3 pb-3">
            <a href="<?php echo esc_url_raw(wsklad()->admin()->utilityAdminAccountsGetUrl('dashboard', $account_id)); ?>" class="wp-heading-inline text-decoration-none fs-5"><?php esc_html_e('Dashboard', 'wsklad'); ?></a>
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