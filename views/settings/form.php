<?php defined('ABSPATH') || exit;?>

<form method="post" action="">
	<?php wp_nonce_field('wsklad-admin-settings-save', '_wsklad-admin-nonce'); ?>
    <div class="wsklad-admin-settings section-border rounded-3 bg-white p-2 mt-2">
        <table class="form-table wsklad-admin-form-table wsklad-admin-settings-form-table">
		    <?php $args['object']->generateHtml($args['object']->getFields(), true); ?>
        </table>
    </div>
    <p class="submit">
	    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save settings', 'wsklad'); ?>">
    </p>
</form>