<?php defined('ABSPATH') || exit;

$label = __('Back to accounts list', 'wsklad');
wsklad()->views()->adminBackLink($label, $args['back_url']);

?>

<?php
$title = __('Error', 'wsklad');
$title = apply_filters('wsklad_admin_accounts_update_error_title', $title);
$text = __('Update is not available. Account not found or unavailable.', 'wsklad');
$text = apply_filters('wsklad_admin_accounts_update_error_text', $text);
?>

<div class="wsklad-accounts-alert mb-2 mt-2">
    <h3><?php esc_html_e($title); ?></h3>
    <p><?php esc_html_e($text); ?></p>
</div>