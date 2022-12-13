<?php defined('ABSPATH') || exit;?>

<div class="row g-0">
    <div class="col-24 col-lg-17">
        <div class="pe-0 pe-lg-2">
            <form method="post" action="<?php echo esc_url(add_query_arg('form', $args['object']->get_id())); ?>">
                <?php wp_nonce_field('wsklad-admin-accounts-update-save', '_wsklad-admin-nonce'); ?>
                <div class="bg-white p-2 rounded-3 wsklad-toc-container">
                    <table class="form-table wsklad-admin-form-table">
                        <?php $args['object']->generate_html($args['object']->get_fields(), true); ?>
                    </table>
                </div>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save account', 'wsklad'); ?>">
                </p>
            </form>
        </div>
    </div>
    <div class="col-24 col-lg-7">
		<?php do_action('wsklad_admin_accounts_update_sidebar_show'); ?>
    </div>
</div>