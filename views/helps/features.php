<?php defined('ABSPATH') || exit; ?>

<h2><?php _e( 'Not a feature?', 'wsklad' ); ?></h2>

<p>
	<?php _e('First of all, you need to make sure - whether the necessary opportunity is really missing.', 'wsklad'); ?>
	<?php _e('It may be worth looking at the available settings or reading the documentation.', 'wsklad'); ?>
</p>

<p>
	<?php _e('Also, before requesting an opportunity, you need to make sure that:', 'wsklad'); ?>
</p>

<ul>
    <li><?php _e('Is the required feature added in WSKLAD updates.', 'wsklad'); ?></li>
    <li><?php _e('Whether the possibility is implemented by an additional extension to WSKLAD.', 'wsklad'); ?></li>
    <li><?php _e('Whether the desired opportunity is waiting for its implementation.', 'wsklad'); ?></li>
</ul>

<p>
	<?php _e('If the feature is added in WSKLAD updates, you just need to install the updated version.', 'wsklad'); ?>
</p>

<p>
	<?php _e('But if the feature is implemented in an extension to WSKLAD, then this feature should not be expected as part of WSKLAD and you need to install the extension.', 'wsklad'); ?>
	<?php _e('Because the feature implemented in the extension is so significant that it needed to create an extension for it.', 'wsklad'); ?>
</p>

<p>
	<a href="//wsklad.ru/features" class="button" target="_blank">
		<?php _e('Features', 'wsklad'); ?>
	</a>
    <a href="//wsklad.ru/extensions" class="button" target="_blank">
		<?php _e('Extensions', 'wsklad'); ?>
    </a>
</p>