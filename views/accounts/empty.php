<?php defined('ABSPATH') || exit;?>

<div class="accounts-empty">
	<h2>
	<?php
		if(!empty($_REQUEST['s']))
		{
			$search_text = sanitize_text_field(wp_unslash($_REQUEST['s']));
			printf('%s <b>%s</b>', esc_html__('No accounts matched this search:', 'wsklad'), esc_html($search_text));
		}
		else
		{
			esc_html_e('No accounts found.', 'wsklad');
		}
	?>
	</h2>

	<p>
		<?php esc_html_e( 'Add at least one Moy Sklad account to continue.', 'wsklad' ); ?>
	</p>

	<a href="<?php echo esc_url_raw(add_query_arg(['page' => 'wsklad_add'])); ?>" class="mt-2 btn-lg d-inline-block page-title-action">
		<?php esc_html_e('Add accounts', 'wsklad'); ?>
	</a>

</div>