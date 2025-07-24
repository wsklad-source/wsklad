<?php defined('ABSPATH') || exit; ?>

<div class="extensions-alert section-border mb-2 mt-2">
    <h3><?php esc_html_e('Extensions not found.', 'wsklad'); ?></h3>
    <p><?php esc_html_e('As soon as the extensions are installed, they will appear in this section.', 'wsklad'); ?></p>

	<?php
	printf
	(
		'<p>%s %s</p>',
        esc_html__('Information about all available official extensions is available on the website:', 'wsklad'),
		'<a href="https://wsklad.ru/extensions" target=_blank>https://wsklad.ru/extensions</a>'
	);
	?>
</div>