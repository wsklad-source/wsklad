<?php defined('ABSPATH') || exit;

use Wsklad\Admin\Settings\ConnectionForm;

/** @var ConnectionForm $object */
$object = $args['object'];

?>

<form method="post" action="">
	<?php wp_nonce_field('wsklad-admin-settings-save', '_wsklad-admin-nonce'); ?>
    <?php if($object->status) : ?>
    <div class="wsklad-admin-settings wsklad-admin-connection bg-white rounded-3 mt-2 mb-2 px-2">
        <table class="form-table wsklad-admin-form-table wsklad-admin-settings-form-table">
		    <?php $object->generateHtml($object->getFields(), true); ?>
        </table>
    </div>
    <?php endif; ?>
    <div class="submit p-0 mt-3">
	    <?php
	        $button = __('Connect by WSKLAD site', 'wsklad');
            if($object->status)
            {
                $button = __('Disconnect', 'wsklad');
            }
        ?>

	    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e($button); ?>">
    </div>
</form>