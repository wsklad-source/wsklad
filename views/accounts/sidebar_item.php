<?php defined('ABSPATH') || exit; ?>

<?php do_action('wsklad_admin_accounts_update_before_sidebar_item_show'); ?>

<div class="card rounded-0 mb-2 mt-0 p-0 section-border w-100" style="<?php if(isset($args['css'])) echo esc_attr($args['css']); ?>">
    <?php if(isset($args['header'])): ?>
    <div class="card-header p-2 rounded-0">
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

<?php do_action('wsklad_admin_accounts_update_after_sidebar_item_show'); ?>