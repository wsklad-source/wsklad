<?php defined('ABSPATH') || exit;

/**
 * ⚠ The local variables are named `wsklad_error_*`, not `$title`/`$text`.
 *
 * `$title` is a WordPress global: it is what sets the admin page heading. This
 * template is included from a global scope, so assigning `$title` here overwrote the
 * heading of whichever screen the user was on — and an error page is exactly the
 * moment a user reads the heading to find out where they are. The rendered text is
 * identical; the heading is not.
 *
 * The two filter names are unchanged: they are public contract, and a variable name
 * was never part of it.
 */

$label = esc_html__('Back to the accounts list', 'wsklad');
wsklad()->views()->adminBackLink($label, $args['back_url']);

?>

<?php
$wsklad_error_title = esc_html__('Error', 'wsklad');
$wsklad_error_title = apply_filters('wsklad_admin_accounts_update_error_title', $wsklad_error_title);
$wsklad_error_text = esc_html__('This account cannot be edited — it was not found.', 'wsklad');
$wsklad_error_text = apply_filters('wsklad_admin_accounts_update_error_text', $wsklad_error_text);
?>

<div class="wsklad-accounts-alert mb-2 mt-2">
    <h3><?php printf('%s', esc_html($wsklad_error_title)); ?></h3>
    <p><?php printf('%s', esc_html($wsklad_error_text)); ?></p>
</div>
