<?php defined('ABSPATH') || exit;

use Wsklad\Admin\Wizards\Setup\Complete;

if(!isset($args['step']))
{
    return;
}

/** @var Complete $wizard */
$step = $args['step'];

?>

<h1><?php esc_html_e('Setup complete', 'wsklad'); ?></h1>
<p><?php esc_html_e('WSKLAD is ready to use.', 'wsklad'); ?></p>

<p class="mt-4 actions step">
    <a href="<?php echo esc_url($args['back_url']); ?>" class="button button-primary button-large button-next">
        <?php esc_html_e('Start using WSKLAD', 'wsklad'); ?>
    </a>
</p>