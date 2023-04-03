<?php defined('ABSPATH') || exit;

$text = sprintf
(
    '%s %s %s <hr>',
    __('Your copy of the free software has not been activated.', 'wsklad'),
    __('We recommend that you activate your copy of the free software for stable updates and better performance.', 'wsklad'),
    __('After activation, this section will disappear and will no longer be shown.', 'wsklad')
);
?>

<div class="row g-0">
    <div class="col-24 col-lg-17 p-0">
        <div class="pe-0 pe-lg-2">
            <div class="alert section-border wsklad-accounts-alert mb-2">
                <p class="fs-6"><?php echo wp_kses_post($text); ?></p>

                <div class="">
                    <h2><?php _e('How to activate?', 'wsklad'); ?></h2>
                    <ul>
                        <li class="fs-6"><b>1.</b> <?php _e('Get an activation code in any available way. For example, on the official website.', 'wsklad'); ?> (<a href="://wsklad.ru/market/code">wsklad.ru/market/code</a>)</li>
                        <li class="fs-6"><b>2.</b> <?php _e('Enter the activation code in the plugin settings.', 'wsklad'); ?> (<a href="<?php printf('%s', get_home_url('', remove_query_arg(['account_id', 'dashboard_section', 'do_action'], add_query_arg(['page' => 'wsklad_settings', 'do_settings' => 'activation'])))); ?>"><?php printf('%s', get_home_url('', remove_query_arg(['account_id', 'dashboard_section', 'do_action'], add_query_arg(['page' => 'wsklad_settings', 'do_settings' => 'activation'])))); ?></a>)</li>
                    </ul>
                </div>

                <div class="">
                    <h2><?php _e('Why is activation required?', 'wsklad'); ?></h2>
                    <p class="fs-6">
                        <?php _e('You received a copy of the software completely free of charge and you can use it as is without any activation.', 'wsklad'); ?>
                        <?php _e('However, in order to receive timely, as well as necessary updates and improvements, it is necessary to activate the current environment.', 'wsklad'); ?>
                    </p>
                    <p class="fs-6">
                        <?php _e('Activation is vital for the performance of the plugin and its further active development. Dont ignore activation.', 'wsklad'); ?>
                        <?php _e('Each activation triggers a mechanism to improve the software you use.', 'wsklad'); ?>
                    </p>
                    <p class="fs-6">
                        <?php _e('In addition to supporting the software you use, additional features will be added.', 'wsklad'); ?>
                    </p>
                </div>

            </div>

        </div>
    </div>
    <div class="col-24 col-lg-7 p-0">

        <div class="alert alert-info border-0" style="max-width: 100%;">
            <h4 class="alert-heading mt-0 mb-1"><?php _e('Do not wait until something breaks!', 'wsklad'); ?></h4>
            <?php _e('Activate your current copy of the software.', 'wsklad'); ?>
            <hr>
            <?php _e('Buy code:', 'wsklad'); ?> <a target="_blank" href="//wsklad.ru/market/code">wsklad.ru/market/code</a>
        </div>

        <div class="alert alert-secondary border-0 mt-2" style="max-width: 100%;">
            <h4 class="alert-heading mt-0 mb-1"><?php _e('No financial opportunity?', 'wsklad'); ?></h4>
            <?php _e('Take part in the development of the solution you use.', 'wsklad'); ?>
            <br/>
            <?php _e('Information on how to participate is available in the official documentation on the official website.', 'wsklad'); ?>
            <hr>
            <?php _e('Docs:', 'wsklad'); ?> <a target="_blank" href="//wsklad.ru/docs">wsklad.ru/docs</a>
        </div>

        <div class="alert alert-secondary border-0 mt-2" style="max-width: 100%;">
            <h4 class="alert-heading mt-0 mb-1"><?php _e('Every activation counts!', 'wsklad'); ?></h4>
            <?php _e('By activating your project, you let the WC1C team know that the plugin is in active use.', 'wsklad'); ?>
            <br/>
            <?php _e('Also, you give a financial opportunity to release compatibility updates and add new features!', 'wsklad'); ?>
        </div>
    </div>
</div>