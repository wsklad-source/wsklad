<?php defined('ABSPATH') || exit;

$label = esc_html__('Back to accounts list', 'wsklad');
wsklad()->views()->adminBackLink($label, $args['back_url']);

?>

<?php
$title = esc_html__('Error', 'wsklad');
$title = apply_filters('wsklad_admin_accounts_update_error_title', $title);
$text = esc_html__('Update is not available. Account not found or unavailable.', 'wsklad');
$text = apply_filters('wsklad_admin_accounts_update_error_text', $text);
?>

<div class="wsklad-accounts-alert mb-2 mt-2">
    <h3><?php printf('%s', esc_html($title)); ?></h3>
    <p><?php printf('%s', esc_html($text)); ?></p>
</div>