<?php defined('ABSPATH') || exit;

use Wsklad\Admin\Wizards\Setup\Database;

if(!isset($args['step']))
{
    return;
}

/** @var Database $wizard */
$step = $args['step'];

?>

<h1><?php esc_html_e('Creating the database tables', 'wsklad'); ?></h1>
<p><?php esc_html_e('Continue to create the tables WSKLAD needs.', 'wsklad'); ?></p>

<form method="post" action="">
<p class="mt-4 actions step">
    <?php wp_nonce_field('wsklad-admin-wizard-database', '_wsklad-admin-nonce'); ?>
    <input type="submit" name="submit" id="submit" class="button button-primary button-large button-next" value="<?php esc_html_e('Start setup', 'wsklad'); ?>">
</p>
</form>