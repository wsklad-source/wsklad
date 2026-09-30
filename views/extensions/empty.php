<?php defined('ABSPATH') || exit; ?>

<div class="extensions-alert section-border mb-2 mt-2">
    <h3><?php esc_html_e('No extensions found.', 'wsklad'); ?></h3>
    <p><?php esc_html_e('Installed extensions appear here.', 'wsklad'); ?></p>

	<?php
	printf
	(
		'<p>%s %s</p>',
        esc_html__('All official extensions are listed on our website:', 'wsklad'),
		'<a href="https://wsklad.ru/extensions" target=_blank>https://wsklad.ru/extensions</a>'
	);
	?>
</div>