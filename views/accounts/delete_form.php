<?php defined('ABSPATH') || exit;?>

<form method="post" action="">
	<?php wp_nonce_field('wsklad-admin-accounts-delete-save', '_wsklad-admin-nonce-accounts-delete'); ?>
    <div class="mt-2 bg-white p-2 pt-1">
        <table class="form-table wsklad-admin-form-table">
            <?php
                if(isset($args) && is_array($args))
                {
                    $args['object']->generate_html($args['object']->get_fields(), true);
                }
            ?>
        </table>
    </div>
    <p class="submit">
	    <input type="submit" name="submit" id="submit" class="button button-danger" value="<?php _e('Delete', 'wsklad'); ?>">
    </p>
</form>