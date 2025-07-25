<?php defined('ABSPATH') || exit;?>

<form method="post" action="">
	<?php wp_nonce_field('wsklad-admin-settings-save', '_wsklad-admin-nonce'); ?>
    <div class="wsklad-admin-settings section-border rounded-3 bg-white p-2 pt-0 mt-2">
        <table class="form-table wsklad-admin-form-table wsklad-admin-settings-form-table">
		    <?php $args['object']->generateHtml($args['object']->getFields(), true); ?>
        </table>
    </div>
    <p class="submit mt-0">
	    <input type="submit" name="submit" id="submit" class="button button-primary p-1 fs-6 px-3" value="<?php esc_html_e('Save settings', 'wsklad'); ?>">
    </p>
</form>