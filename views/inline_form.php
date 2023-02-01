<?php defined('ABSPATH') || exit;?>

<form method="post" action="<?php echo esc_url(add_query_arg('form', $args['object']->getId())); ?>">
	<?php wp_nonce_field('wsklad-admin-'.$args['object']->getId().'-save', '_wsklad-admin-nonce-' . $args['object']->getId()); ?>

    <?php $args['object']->generateHtml($args['object']->getFields(), true); ?>
</form>