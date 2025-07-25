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

<h1><?php esc_html_e('Welcome to WSKLAD!', 'wsklad'); ?></h1>
<p><?php esc_html_e('Thank you for choosing WSKLAD to website! This is only complete solution for integrating WordPress with Moy Sklad.', 'wsklad'); ?></p>

<p><?php esc_html_e('This quick setup wizard will help you configure the basic settings.', 'wsklad'); ?></p>

<?php if(0 < (int)wsklad()->environment()->get('php_max_execution_time') && 10 > (int)wsklad()->environment()->get('php_max_execution_time')) : ?>
<?php $available = false; ?>
<p><?php esc_html_e('PHP scripts execution time is less than 10 seconds. WSKLAD requires at least 20. Set php_max_execution_time to more than 20 seconds.', 'wsklad'); ?></p>
<?php endif; ?>

<?php if($available) : ?>
<p><strong><?php esc_html_e('Its should not take longer than five minutes.', 'wsklad'); ?></strong></p>
<p class="mt-4 actions step">
    <a href="<?php echo esc_url($step->wizard()->getNextStepLink()); ?>" class="button button-primary button-large button-next">
        <?php esc_html_e('Lets Go!', 'wsklad'); ?>
    </a>
</p>
<?php endif; ?>

<?php if(!$available) : ?>
    <p><strong><?php esc_html_e('Need to fix the compatibility errors and return to the setup wizard.', 'wsklad'); ?></strong></p>
<?php endif;
