<?php defined('ABSPATH') || exit;

use Wsklad\Admin\Wizards\Setup\Complete;

if(!isset($args['step']))
{
    return;
}

/** @var Complete $wizard */
$step = $args['step'];

?>

<h1><?php esc_html_e('Installation completed!', 'wsklad'); ?></h1>
<p><?php esc_html_e('Now you can proceed to using the WSKLAD plugin.', 'wsklad'); ?></p>

<p class="mt-4 actions step">
    <a href="<?php echo esc_url($args['back_url']); ?>" class="button button-primary button-large button-next">
        <?php esc_html_e('Go to use', 'wsklad'); ?>
    </a>
</p>