<?php defined('ABSPATH') || exit;?>

<div class="row g-0">
    <div class="col-24">
        <div class="px-2">
            <?php
                if(isset($args['back_url']))
                {
                    $label = __('Back to accounts list', 'wsklad');
                    wsklad()->views()->adminBackLink($label, $args['back_url']);
                }
                else
                {
                    echo '<div class="mt-2"></div>';
                }
            ?>
        </div>
        <div class="p-2 bg-white rounded-0 mb-3">
            <?php do_action('wsklad_admin_accounts_update_header_show'); ?>
        </div>
    </div>
</div>

<?php do_action('wsklad_admin_before_accounts_update_show'); ?>

<?php do_action('wsklad_admin_accounts_update_show'); ?>

<?php do_action('wsklad_admin_after_accounts_update_show'); ?>