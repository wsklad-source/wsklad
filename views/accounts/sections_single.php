<?php defined('ABSPATH') || exit;?>

<?php
    $account_id = 0;
    if(!empty($_GET['account_id']))
    {
        $account_id = $search_text = sanitize_text_field(wp_unslash($_GET['account_id']));
    }
?>

<div class="row g-0">
    <div class="col-24">
        <div class="p-2 pt-3 pb-3">
            <a href="<?php echo esc_url_raw(wsklad()->admin()->utilityAdminAccountsGetUrl('dashboard', $account_id)); ?>" class="wp-heading-inline text-decoration-none fs-5"><?php esc_html_e('Dashboard', 'wsklad'); ?></a>
            >
            <span class="wp-heading-inline fs-5"><?php echo esc_html($args['name']); ?></span>
        </div>
    </div>
</div>

<?php do_action('wsklad_admin_before_accounts_sections_single_show'); ?>

<?php do_action('wsklad_admin_accounts_sections_single_show'); ?>

<?php do_action('wsklad_admin_after_accounts_sections_single_show'); ?>