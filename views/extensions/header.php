<?php defined('ABSPATH') || exit; ?>

<h1 class="wp-heading-inline"><?php _e('Extensions', 'wsklad'); ?></h1>

<hr class="wp-header-end">

<?php
if(wsklad()->context()->isAdmin())
{
	wsklad()->admin()->notices()->output();
}
?>