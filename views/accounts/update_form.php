<?php defined('ABSPATH') || exit;?>

<?php do_action('wsklad_admin_accounts_update_form_before_show'); ?>

<form method="post" action="<?php echo esc_url(add_query_arg('form', $args['object']->getId())); ?>">
    <?php wp_nonce_field('wsklad-admin-accounts-update-save', '_wsklad-admin-nonce'); ?>
    <div class="bg-white p-2 rounded-3 wsklad-toc-container section-border">
        <table class="form-table wsklad-admin-form-table">
            <?php $args['object']->generateHtml($args['object']->getFields(), true); ?>
        </table>
    </div>
    <p class="submit">
        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save account', 'wsklad'); ?>">
    </p>
</form>

<?php do_action('wsklad_admin_accounts_update_form_after_show'); ?>
