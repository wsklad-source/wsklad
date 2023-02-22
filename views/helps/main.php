<?php defined('ABSPATH') || exit; ?>

<?php
printf
(
    '<p>%s %s</p>',
    __('If no understand how Integration with Moy Sklad works, how to use and supplement it, can view the documentation.', 'wsklad'),
    __('Documentation contains all kinds of resources such as code snippets, user guides and more.', 'wsklad')
);
?>

<a href="//wsklad.ru/docs" target="_blank" class="button button-primary">
    <?php _e('Documentation', 'wsklad'); ?>
</a>

<?php
    if(has_action('wsklad_admin_help_main_show'))
    {
        echo '<hr>';
        do_action('wsklad_admin_help_main_show');
    }
?>