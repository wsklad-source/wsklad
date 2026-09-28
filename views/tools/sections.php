<?php defined('ABSPATH') || exit;

/**
 * Tools section list.
 *
 * `Admin\Tools::wrapSections()` renders this. It renders the tool list rather than the
 * generic `SectionsTrait` sections, because the tools screen is a flat catalogue: each
 * entry is a tool with a name and a description, not a page section.
 *
 * @var array $args Expects 'object' => Wsklad\Admin\Tools
 */

$wsklad_tools_screen = $args['object'];

$wsklad_views = [];

foreach($wsklad_tools_screen->tools as $wsklad_tool_id => $wsklad_tool_object)
{
	if(!is_object($wsklad_tool_object))
	{
		try
		{
			$wsklad_tool_object = wsklad()->tools()->init($wsklad_tool_id);
		}
		catch(\Wsklad\Exceptions\Exception $e)
		{
			continue;
		}
	}

	$wsklad_class = $wsklad_tools_screen->getCurrentToolId() === $wsklad_tool_id ? ' active' : '';

	$wsklad_views[$wsklad_tool_id] = sprintf
	(
		'<a href="%s" class="nav-link w-auto m-1 mt-0 mb-1 p-2 text-decoration-none%s">%s<br><span class="sub mt-1">%s</span></a>',
		esc_url($wsklad_tools_screen->utilityAdminToolsGetUrl($wsklad_tool_id)),
		$wsklad_class,
		esc_html($wsklad_tool_object->getName()),
		esc_html($wsklad_tool_object->getDescription())
	);
}

unset($wsklad_tools_screen, $wsklad_tool_id, $wsklad_tool_object, $wsklad_class);

if(count($wsklad_views) < 1)
{
	return;
}

echo "<div class='container'>";
echo "<div class='menu row pt-0 p-0'>";

foreach($wsklad_views as $wsklad_key => $wsklad_view)
{
	$wsklad_views[$wsklad_key] = "<div class='col-12 p-0 nav-item" . esc_attr($wsklad_key) . "'>" . $wsklad_view;
}

echo wp_kses_post(implode('</div>', $wsklad_views) . '</div>');
echo '</div>';
echo '</div>';
