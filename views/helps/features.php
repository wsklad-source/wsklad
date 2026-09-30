<?php defined('ABSPATH') || exit; ?>

<h2><?php esc_html_e( 'Not a feature?', 'wsklad' ); ?></h2>

<p>
	<?php esc_html_e('First, make sure the feature is really missing.', 'wsklad'); ?>
	<?php esc_html_e('It may already be in the settings, or in the documentation.', 'wsklad'); ?>
</p>

<p>
	<?php esc_html_e('Before requesting a feature, please check:', 'wsklad'); ?>
</p>

<ul>
    <li><?php esc_html_e('Whether a WSKLAD update already added it.', 'wsklad'); ?></li>
    <li><?php esc_html_e('Whether an extension provides it instead.', 'wsklad'); ?></li>
    <li><?php esc_html_e('Whether it is already on the roadmap.', 'wsklad'); ?></li>
</ul>

<p>
	<?php esc_html_e('If an update added it, install that version.', 'wsklad'); ?>
</p>

<p>
	<?php esc_html_e('If it lives in an extension, it will not arrive in WSKLAD itself — install the extension.', 'wsklad'); ?>
	<?php esc_html_e('Some features are substantial enough to live in an extension of their own.', 'wsklad'); ?>
</p>

<p>
	<a href="//wsklad.ru/features" class="button" target="_blank">
		<?php esc_html_e('Features', 'wsklad'); ?>
	</a>
    <a href="//wsklad.ru/extensions" class="button" target="_blank">
		<?php esc_html_e('Extensions', 'wsklad'); ?>
    </a>
</p>