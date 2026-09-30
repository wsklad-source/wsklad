<?php defined('ABSPATH') || exit; ?>

<?php
printf
(
    '<p>%s %s</p>',
    esc_html__('If you are not sure how the Moy Sklad integration works or what you can do with it, start with the documentation.', 'wsklad'),
    esc_html__('The documentation holds user guides, code examples and more.', 'wsklad')
);
?>

<a href="//wsklad.ru/docs" target="_blank" class="button button-primary">
    <?php esc_html_e('Documentation', 'wsklad'); ?>
</a>

<?php
    if(has_action('wsklad_admin_help_main_show'))
    {
        echo '<hr>';
        do_action('wsklad_admin_help_main_show');
    }
?>