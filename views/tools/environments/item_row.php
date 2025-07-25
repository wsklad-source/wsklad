<?php defined('ABSPATH') || exit; ?>

<tr class="">
    <td class="" style="width: 40%;">
        <b><?php printf('%s', esc_html($args['title'])); ?></b>
    </td>
    <td class="">
	    <?php
            $data = apply_filters('wsklad_admin_report_data_row_print', $args['data']);
            printf('%s', esc_html($data));
	    ?>
    </td>
</tr>