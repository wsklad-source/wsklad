<?php defined('ABSPATH') || exit; ?>

<?php do_action('wsklad_admin_accounts_update_before_sidebar_toc_show'); ?>

<div class="card wsklad-sidebar-toc mt-0 mb-2 p-0 mw-100 w-100">
    <?php if(isset($args['header'])): ?>
    <div class="card-header p-2">
        <?php printf('%s', wp_kses_post($args['header'])); ?>
    </div>
    <?php endif; ?>
    <?php if(isset($args['body'])): ?>
    <div class="card-body p-0">
        <?php printf('%s', wp_kses_post($args['body'])); ?>
    </div>
    <?php endif; ?>
    <?php if(isset($args['footer'])): ?>
    <div class="card-footer p-2">
        <?php printf('%s', wp_kses_post($args['footer'])); ?>
    </div>
	<?php endif; ?>
</div>

<?php do_action('wsklad_admin_accounts_update_after_sidebar_toc_show'); ?>