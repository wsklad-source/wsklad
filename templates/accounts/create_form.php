<?php defined('ABSPATH') || exit;?>

<form method="post" action="">
	<?php wp_nonce_field('wsklad-admin-accounts-create-save', '_wsklad-admin-nonce-accounts-create'); ?>
    <div class="bg-white p-2 pt-1">
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
	    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Connect', 'wsklad'); ?>">
    </p>
</form>