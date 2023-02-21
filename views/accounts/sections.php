<?php defined('ABSPATH') || exit;

$update = $args['object'];

$views = [];

foreach($update->getSections() as $tab_key => $tab_name)
{
	if(!isset($tab_name['visible']) && $tab_name['title'] !== true)
	{
		continue;
	}

	if(!isset($tab_name['description']))
	{
		$tab_name['description'] = 'Описание отсутствует.';
	}

	$class = $update->getCurrentSection() === $tab_key ? ' active' :'';
	$sold_url = esc_url(add_query_arg($update->getSectionKey(), $tab_key));

	$views[$tab_key] = sprintf
	(
		'<a href="%s" class="nav-link w-auto m-1 mt-0 mb-2 p-2 text-decoration-none %s">%s<br><span class="sub">%s</span></a>',
		$sold_url,
		$class,
		$tab_name['title'],
		$tab_name['description']
	);
}

if(count($views) < 1)
{
	return;
}
echo "<div class='container'>";
echo "<div class='menu row pt-0 p-0'>";
foreach($views as $class => $view)
{
	$views[$class] = "<div class='col-12 p-0 nav-item $class'>$view";
}
echo implode("</div>", $views) . "</div>";
echo '</div>';
echo '</div>';