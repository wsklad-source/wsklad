<?php defined('ABSPATH') || exit; ?>

<div class="bg-white section-border rounded-3 mb-2 mt-2 w-100">
    <div class="card-body p-3">
        <h2 class="card-title mt-0">
            <?php printf('%s', sanitize_text_field($args['name'])); ?>
        </h2>
        <p class="card-text">
            <?php printf('%s', wp_kses_post($args['description'])); ?>
        </p>
    </div>
    <div class="card-footer rounded-3 bg-light p-3">
       <a class="text-decoration-none button button-primary" href="<?php echo esc_url($args['url']); ?>">
	       <?php _e('Open', 'wsklad'); ?>
       </a>
    </div>
</div>