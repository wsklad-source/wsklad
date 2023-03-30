<?php defined('ABSPATH') || exit;?>

<form method="post" action="" class="mt-2">
    <?php wp_nonce_field('wsklad-admin-add-save', '_wsklad-admin-nonce-add'); ?>
    <div class="row g-0">
        <div class="col-24 col-lg-17">
            <div class="pe-0 pe-lg-2">
                <div class="bg-white p-2 mb-2 rounded-3 section-border">
                    <table class="form-table wsklad-form-table bg-white">
						<?php
						if(isset($args) && is_array($args))
						{
							$args['object']->generateHtml($args['object']->getFields(), true);
						}
						?>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-24 col-lg-7">
			<?php do_action('wsklad_admin_add_sidebar_after_show'); ?>

            <div class="card border-0 mt-0 p-0 w-100">
                <div class="card-body p-3">
					<?php _e('Enter a name for the new account and click the add account button.', 'wsklad'); ?>
                </div>
                <div class="card-footer p-3">
                    <p class="submit p-0 m-0">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Add account', 'wsklad'); ?>">
                    </p>
                </div>
            </div>

			<?php do_action('wsklad_admin_add_sidebar_after_show'); ?>
        </div>
    </div>
</form>