<?php defined('ABSPATH') || exit;

$text = sprintf
(
    '%s %s<hr>%s',
    __('Install the log viewer extension to read the logs. They record what the plugin did and what went wrong.', 'wsklad'),
    __('When something misbehaves, the logs are the first place to look. Without the extension you can open them over FTP.', 'wsklad'),
    __('Once the extension is installed, it will show its log viewer here.', 'wsklad')
);

$img = wsklad()->environment()->get('plugin_directory_url') . 'assets/images/promo_logs.png';

?>

<div class="section-border alert wsklad-accounts-alert">
    <div class="mb-3 mt-1">
        <p class="fs-6"><?php echo wp_kses_post($text); ?></p>
    </div>

    <img src="<?php echo esc_url($img); ?>" class="card-img" alt="Logs">
</div>
