<?php defined('ABSPATH') || exit; ?>

<div class="wrap mt-0 me-0">
    <?php do_action('wsklad_admin_header_show'); ?>
</div>

<div class="wrap mt-0">
    <?php
        if(wsklad()->context()->isAdmin())
        {
            wsklad()->admin()->notices()->output();
        }
    ?>
    <?php do_action('wsklad_admin_show'); ?>
</div>