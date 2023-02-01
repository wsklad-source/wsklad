<?php defined('ABSPATH') || exit;

$update = $args['object'];

$views = [];

foreach($update->getSections() as $tab_key => $tab_name)
{
	if(!isset($tab_name['visible']) && $tab_name['title'] !== true)
	{
		continue;
	}

	$class = $update->getCurrentSection() === $tab_key ? ' active' :'';
	$sold_url = esc_url(add_query_arg($update->getSectionKey(), $tab_key));

	$views[$tab_key] = sprintf
	(
		'<a href="%s" class="nav-link p-0 text-decoration-none %s">%s </a>',
		$sold_url,
		$class,
		$tab_name['title']
	);
}

if(count($views) < 1)
{
	return;
}
echo "<div class='container'>";
echo "<div class='menu row pt-0 gx-0'>";
foreach($views as $class => $view)
{
	$views[$class] = "<div class='col-12 col-md-6 md-mt-2 mb-3 nav-item $class'>$view";
}
echo implode("</div>", $views) . "</div>";
echo '</div>';
echo '</div>';