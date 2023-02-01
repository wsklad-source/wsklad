<?php defined('ABSPATH') || exit;

$admins = \Wsklad\Admin\Connections::instance();

echo "<nav class='nav-tab-wrapper woo-nav-tab-wrapper'>";

foreach($admins->getSections() as $tab_key => $tab_name)
{
	if(!isset($tab_name['visible']) && $tab_name['title'] !== true)
	{
		continue;
	}

	$class = $admins->getCurrentSection() === $tab_key ? ' class="nav-tab nav-tab-active"' : ' class="nav-tab"';
	$sold_url = esc_url(add_query_arg('do_connection', $tab_key));

	printf
	(
		'<a href="%s" %s>%s</a>',
		$sold_url,
		$class,
		$tab_name['title']
	);
}

echo '</nav>';