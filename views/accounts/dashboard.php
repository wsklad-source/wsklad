<?php defined('ABSPATH') || exit;?>

<div class="row g-0">
    <div class="col-24">
        <div class="p-2 bg-white rounded-0 mb-3">
            <?php do_action('wsklad_admin_accounts_dashboard_header_show'); ?>
        </div>
    </div>
</div>

<?php do_action('wsklad_admin_before_accounts_dashboard_show'); ?>

<?php do_action('wsklad_admin_accounts_dashboard_show'); ?>

<?php do_action('wsklad_admin_after_accounts_dashboard_show'); ?>