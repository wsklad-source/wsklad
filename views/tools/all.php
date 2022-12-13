<?php defined('ABSPATH') || exit; ?>

<div class="bg-white p-1 px-2 rounded-3 mt-2">
	<?php do_action('wsklad_admin_tools_all_before_show'); ?>

	<?php

        foreach($args['object']->tools as $tool_id => $tool_object)
        {
            if(!is_object($tool_object))
            {
	            try
	            {
		            $tool_object = wsklad()->tools()->init($tool_id);
	            }
	            catch(\Wsklad\Exceptions\Exception $e)
	            {
                    continue;
	            }
            }

	        $args =
            [
                'id' => $tool_id,
                'name' => $tool_object->getName(),
                'description' => $tool_object->getDescription(),
                'url' => $args['object']->utilityAdminToolsGetUrl($tool_id),
                'object' => $tool_object,
            ];

            wsklad()->views()->getView('tools/item.php', $args);
        }

    ?>

	<?php do_action('wsklad_admin_tools_all_after_show'); ?>
</div>