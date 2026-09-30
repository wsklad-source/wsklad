<?php defined('ABSPATH') || exit; ?>

<h2><?php esc_html_e( 'Found a bug?', 'wsklad' ); ?></h2>

<p>
    <?php esc_html_e('First, make sure it is a bug, and that a recent update has not already fixed it.', 'wsklad'); ?>
	<?php esc_html_e('If an update fixes it, installing that version is all you need to do.', 'wsklad'); ?>
</p>
<p>
	<?php esc_html_e('Before reporting a bug, please check:', 'wsklad'); ?>
</p>

<ul>
	<li><?php esc_html_e('That the WordPress, WSKLAD and extension settings are correct.', 'wsklad'); ?></li>
    <li><?php esc_html_e('That WordPress, WSKLAD and the extensions are versions that work together — the Environments screen lists what is compatible.', 'wsklad'); ?></li>
</ul>

<p>
	<?php esc_html_e('If the settings are right, everything is up to date and the bug is still there, please report it.', 'wsklad'); ?>
	<?php esc_html_e('Report the bug using whichever channel you have. You will need a valid technical support code for the project the bug happened in.', 'wsklad'); ?>
</p>

<p>
	<a href="<?php echo esc_url_raw(admin_url('admin.php?page=wsklad_tools&section=tools&tool_id=environments')); ?>" class="button">
		<?php esc_html_e('Environments', 'wsklad'); ?>
	</a>
</p>