<?php defined('ABSPATH') || exit;

use Wsklad\Admin\Wizards\Setup\Check;

if(!isset($args['step']))
{
    return;
}

/** @var Check $wizard */
$step = $args['step'];
$available = true;
?>

<h1><?php esc_html_e('Welcome to WSKLAD', 'wsklad'); ?></h1>
<p><?php esc_html_e('Thank you for choosing WSKLAD — a complete integration between WordPress and Moy Sklad.', 'wsklad'); ?></p>

<p><?php esc_html_e('This wizard sets up the essentials. It takes about five minutes.', 'wsklad'); ?></p>

<?php if(0 < (int)wsklad()->environment()->get('php_max_execution_time') && 10 > (int)wsklad()->environment()->get('php_max_execution_time')) : ?>
<?php $available = false; ?>
<p><?php esc_html_e('PHP is allowed to run for less than 10 seconds. WSKLAD needs at least 20 — raise php_max_execution_time to 20 or more.', 'wsklad'); ?></p>
<?php endif; ?>

<?php if($available) : ?>
<p><strong><?php esc_html_e('It should take no more than five minutes.', 'wsklad'); ?></strong></p>
<p class="mt-4 actions step">
    <a href="<?php echo esc_url($step->wizard()->getNextStepLink()); ?>" class="button button-primary button-large button-next">
        <?php esc_html_e('Start setup', 'wsklad'); ?>
    </a>
</p>
<?php endif; ?>

<?php if(!$available) : ?>
    <p><strong><?php esc_html_e('Fix the compatibility errors to carry on.', 'wsklad'); ?></strong></p>
<?php endif;
