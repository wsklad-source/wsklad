<?php defined('ABSPATH') || exit; ?>

<div class="wsklad-admin-settings section-border rounded-3 bg-white p-2 pt-0 mt-2">
	<p class="fs-6 mb-2"><strong><?php esc_html_e('This tab has nothing to configure.', 'wsklad'); ?></strong></p>

	<p class="fs-6 mb-2"><?php esc_html_e('The two options it used to offer were removed because nothing in the plugin read them: ticking either box changed no behaviour. The values you chose are still stored, and an extension that reads them still gets them back. There is simply no setting left to change here.', 'wsklad'); ?></p>

	<p class="fs-6 mb-0">
		<a href="<?php echo esc_url(add_query_arg('do_settings', 'main')); ?>"><?php esc_html_e('Go to Main settings', 'wsklad'); ?></a>
	</p>
</div>
