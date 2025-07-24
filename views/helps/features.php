<?php defined('ABSPATH') || exit; ?>

<h2><?php esc_html_e( 'Not a feature?', 'wsklad' ); ?></h2>

<p>
	<?php esc_html_e('First of all, you need to make sure - whether the necessary opportunity is really missing.', 'wsklad'); ?>
	<?php esc_html_e('It may be worth looking at the available settings or reading the documentation.', 'wsklad'); ?>
</p>

<p>
	<?php esc_html_e('Also, before requesting an opportunity, you need to make sure that:', 'wsklad'); ?>
</p>

<ul>
    <li><?php esc_html_e('Is the required feature added in WSKLAD updates.', 'wsklad'); ?></li>
    <li><?php esc_html_e('Whether the possibility is implemented by an additional extension to WSKLAD.', 'wsklad'); ?></li>
    <li><?php esc_html_e('Whether the desired opportunity is waiting for its implementation.', 'wsklad'); ?></li>
</ul>

<p>
	<?php esc_html_e('If the feature is added in WSKLAD updates, you just need to install the updated version.', 'wsklad'); ?>
</p>

<p>
	<?php esc_html_e('But if the feature is implemented in an extension to WSKLAD, then this feature should not be expected as part of WSKLAD and you need to install the extension.', 'wsklad'); ?>
	<?php esc_html_e('Because the feature implemented in the extension is so significant that it needed to create an extension for it.', 'wsklad'); ?>
</p>

<p>
	<a href="//wsklad.ru/features" class="button" target="_blank">
		<?php esc_html_e('Features', 'wsklad'); ?>
	</a>
    <a href="//wsklad.ru/extensions" class="button" target="_blank">
		<?php esc_html_e('Extensions', 'wsklad'); ?>
    </a>
</p>