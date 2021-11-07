<?php defined('ABSPATH') || exit;?>

<form method="post" action="">
	<?php wp_nonce_field('wsklad-admin-settings-save', '_wsklad-admin-nonce'); ?>
    <div class="wsklad-admin-settings wsklad-admin-connection">
        <table class="form-table wsklad-admin-form-table wsklad-admin-settings-form-table">
		    <?php $args['object']->generate_html($args['object']->get_fields(), true); ?>
        </table>
    </div>
    <p class="submit">
	    <?php
            $connection_state = $args['object']->getSettings()->isConnected();
            $button = __('Connect by Login & Password', 'wsklad');
            if($connection_state)
            {
                $button = __('Disconnect', 'wsklad');
            }
        ?>
	    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $button; ?>">
    </p>
</form>