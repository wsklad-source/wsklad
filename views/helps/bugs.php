<?php defined('ABSPATH') || exit; ?>

<h2><?php _e( 'Found a bug?', 'wsklad' ); ?></h2>

<p>
    <?php _e('First of all, you need to make sure that a bug has been found and that it has not been fixed in updates before.', 'wsklad'); ?>
	<?php _e('If the bug is fixed in the updates, you just need to install the corrected version.', 'wsklad'); ?>
</p>
<p>
	<?php _e('Before reporting an error need to check:', 'wsklad'); ?>
</p>

<ul>
	<li><?php _e('Whether the settings for WordPress, WSKLAD and their extensions are correct.', 'wsklad'); ?></li>
    <li><?php _e('Whether compatible versions of WordPress, WSKLAD and their extensions are used. Compatibility can be found in the Environments section.', 'wsklad'); ?></li>
</ul>

<p>
	<?php _e('If all settings are made correctly and compatible products of the latest versions are used, but the error is still present, you must report it.', 'wsklad'); ?>
	<?php _e('Report a bug using the methods available to you. When reporting a bug, you must have a valid technical support code for the project on which the bug occurred.', 'wsklad'); ?>
</p>

<p>
	<a href="<?php echo esc_url_raw(admin_url('admin.php?page=wsklad_tools&section=tools&tool_id=environments')); ?>" class="button">
		<?php _e('Environments', 'wsklad'); ?>
	</a>
</p>