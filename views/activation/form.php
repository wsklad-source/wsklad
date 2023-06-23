<?php defined('ABSPATH') || exit;

use Wsklad\Admin\Settings\ConnectionForm;

/** @var ConnectionForm $object */
$object = $args['object'];

?>

<form method="post" action="">
    <div class="row g-0">
        <div class="col-24 col-lg-17">
            <div class="pe-0 pe-lg-2">
	            <?php wp_nonce_field('wsklad-admin-settings-save', '_wsklad-admin-nonce'); ?>
                <div class="section-border wsklad-admin-settings wsklad-admin-connection bg-white rounded-3 mt-2 mb-2 px-2">
                    <table class="form-table wsklad-admin-form-table wsklad-admin-settings-form-table">
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
			<?php do_action('wsklad_admin_settings_activation_sidebar_before_show'); ?>

            <div class="alert alert-warning border-0 mb-4 mt-2 mw-100">
                <h4 class="alert-heading mt-0 mb-1"><?php _e('Get code', 'wsklad'); ?></h4>
				<?php _e('The code can be obtained from the plugin website.', 'wsklad'); ?>
                <hr>
				<?php _e('Site:', 'wsklad'); ?> <a target="_blank" href="//wsklad.ru/market/code">wsklad.ru/market/code</a>
            </div>

            <div class="alert alert-secondary border-0 mt-2 mw-100">
                <h4 class="alert-heading mt-0 mb-1"><?php _e('No financial opportunity?', 'wsklad'); ?></h4>
                <?php _e('Take part in the development of the solution you use.', 'wsklad'); ?>
                <br/>
                <?php _e('Information on how to participate is available in the official documentation on the official website.', 'wsklad'); ?>
                <hr>
                <?php _e('Docs:', 'wsklad'); ?> <a target="_blank" href="//wsklad.ru/docs">wsklad.ru/docs</a>
            </div>

            <div class="alert alert-secondary border-0 mt-2 mw-100">
                <h4 class="alert-heading mt-0 mb-1"><?php _e('Every activation counts!', 'wsklad'); ?></h4>
                <?php _e('By activating your project, you let the WSKLAD team know that the plugin is in active use.', 'wsklad'); ?>
                <br/>
                <?php _e('Also, you give a financial opportunity to release compatibility updates and add new features!', 'wsklad'); ?>
            </div>

			<?php do_action('wsklad_admin_settings_activation_sidebar_after_show'); ?>
        </div>
    </div>
</form>