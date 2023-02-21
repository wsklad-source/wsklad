<?php defined('ABSPATH') || exit; ?>

<?php do_action('wsklad_admin_before_tools_single_show'); ?>

<div class="row g-0">
    <div class="col">
        <div class="px-2">
            <?php wsklad()->views()->adminBackLink($args['name'], $args['back_url']); ?>
        </div>
        <div class="section-border bg-white p-2 rounded-3">
            <?php do_action('wsklad_admin_tools_single_show'); ?>
        </div>
    </div>
</div>

<?php do_action('wsklad_admin_after_tools_single_show'); ?>