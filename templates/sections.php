<?php
/**
 * Namespace
 */
namespace Wsklad;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Show
 */
$admin = Admin::instance();

$nav = '<nav class="nav-tab-wrapper woo-nav-tab-wrapper">';

foreach($admin->getSections() as $tab_key => $tab_name)
{
    if($tab_key === $admin->getCurrentSection())
    {
        $nav .= '<a href="' . admin_url('admin.php?page=wsklad&section=' . $tab_key) . '" class="nav-tab nav-tab-active">' . $tab_name['title'] . '</a>';
    }
    else
    {
        $nav .= '<a href="' . admin_url('admin.php?page=wsklad&section=' . $tab_key) . '" class="nav-tab ">' . $tab_name['title'] . '</a>';
    }
}

$nav .= '</nav>';

echo $nav;