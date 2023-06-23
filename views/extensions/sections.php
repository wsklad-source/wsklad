<?php defined('ABSPATH') || exit;

$admins = \Wsklad\Admin\Extensions::instance();

$views = [];

foreach($admins->getSections() as $tab_key => $tab_name)
{
    $tab_key = esc_attr($tab_key);

    if(!isset($tab_name['visible']) && $tab_name['title'] !== true)
    {
        continue;
    }

    $class = $admins->getCurrentSection() === $tab_key ? ' class="current"' : '';
    $sold_url = esc_url(add_query_arg('do_extensions', $tab_key));

    $views[$tab_key] = sprintf
    (
        '<a href="%s" %s>%s</a>',
        $sold_url,
        $class,
        esc_html($tab_name['title'])
    );
}

echo "<ul class='subsubsub w-100 d-block float-none fs-6'>";
foreach($views as $class => $view)
{
    $views[$class] = "<li class='$class'>$view";
}
echo wp_kses_post(implode(" | </li>", $views) . "</li>");
echo '</ul>';