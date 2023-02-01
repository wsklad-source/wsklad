<?php defined('ABSPATH') || exit; ?>

<?php
    $title = __('Connection', 'wsklad');
    $title = apply_filters('wsklad_admin_settings_connect_title', $title);

    $text = sprintf
    (
            '<p>%s</p> %s',
            __('To receive support and official services, need to go through the authorization of external applications.', 'wsklad'),
            __('Authorization of an external app occurs by going to the official WSKLAD and returning to the current site.', 'wsklad')
    );

    $text = apply_filters('wsklad_admin_settings_connect_text', $text);
?>

<div class="wsklad-accounts-alert mb-2 mt-2">
    <h3><?php echo $title; ?></h3>
    <p><?php echo $text; ?></p>
</div>