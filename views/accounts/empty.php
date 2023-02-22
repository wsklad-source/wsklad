<?php defined('ABSPATH') || exit;?>

<div class="accounts-empty">
	<h2>
	<?php
		if(!empty($_REQUEST['s']))
		{
			$search_text = sanitize_text_field(wp_unslash($_REQUEST['s']));
			printf('%s <b>%s</b>', __('Accounts by query is not found, query:', 'wsklad'), $search_text);
		}
		else
		{
			esc_html_e('Accounts not found.', 'wsklad');
		}
	?>
	</h2>

	<p>
		<?php esc_html_e( 'To continue working, you must add at least one account from Moy Sklad.', 'wsklad' ); ?>
	</p>

	<a href="<?php echo esc_url_raw(add_query_arg(['page' => 'wsklad_add'])); ?>" class="mt-2 btn-lg d-inline-block page-title-action">
		<?php _e('Add accounts', 'wsklad'); ?>
	</a>

</div>