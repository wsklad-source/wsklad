<?php defined('ABSPATH') || exit;

$admins = \Wsklad\Admin\Settings::instance();

$views = [];

foreach($admins->getSections() as $tab_key => $tab_name)
{
    $tab_key = esc_attr($tab_key);

    // `visible` is the switch that says whether a tab is offered, and until now it
    // decided nothing: the guard below required `visible` to be *absent*, so the
    // `visible => false` a section could declare was read as "no opinion" and the
    // tab was drawn anyway. A tab declared hidden stayed visible.
    //
    // Only that one case moves. A section that declares no `visible` at all keeps
    // behaving exactly as before - dropped from the bar, unless it sets `title`
    // to true - so an extension registering a section the old way is unaffected.
    if((!isset($tab_name['visible']) || !$tab_name['visible']) && $tab_name['title'] !== true)
    {
        continue;
    }

    $class = $admins->getCurrentSection() === $tab_key ? ' class="current"' : '';
    $sold_url = esc_url(add_query_arg('do_settings', $tab_key));

    $views[$tab_key] = sprintf
    (
        '<a href="%s" %s>%s</a>',
        $sold_url,
        $class,
        esc_html($tab_name['title'])
    );
}

if(count($views) < 2)
{
    return;
}

echo "<ul class='subsubsub w-100 mw-100 px-2 d-block float-none fs-6'>";
foreach($views as $class => $view)
{
    $views[$class] = "<li class='$class'>$view";
}
echo wp_kses_post(implode(" | </li>", $views) . "</li>");
echo '</ul>';