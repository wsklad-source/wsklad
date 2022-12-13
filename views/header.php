<?php defined('ABSPATH') || exit; ?>

<h1 class="wp-heading-inline"><?php _e('Мой Склад', 'wsklad'); ?></h1>

<a href="<?php echo $args['url_create']; ?>" class="page-title-action">
    <?php _e('New account', 'wsklad'); ?>
</a>

<hr class="wp-header-end">

<?php
    if(wsklad()->context()->isAdmin())
    {
        wsklad()->admin()->notices()->output();
    }
?>