<?php defined('ABSPATH') || exit;

$text = sprintf
(
    '%s %s<hr>%s',
    __('Viewing logs is possible with the extension installed. The logs contain operation information and error information.', 'wsklad'),
    __('If something does not work, or it is not clear how it works, look at the logs. Without a log viewer extension, they can be viewed via FTP.', 'wsklad'),
    __('After installing the extension, this section will be filled with extension features for viewing logs.', 'wsklad')
);

$img = wsklad()->environment()->get('plugin_directory_url') . 'assets/images/promo_logs.png';

?>

<div class="section-border alert wsklad-accounts-alert">
    <div class="mb-3 mt-1">
        <p class="fs-6"><?php echo wp_kses_post($text); ?></p>
    </div>

    <img src="<?php echo esc_url($img); ?>" class="card-img" alt="Logs">
</div>
