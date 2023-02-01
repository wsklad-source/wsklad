<?php defined('ABSPATH') || exit; ?>

<h2><?php _e( 'Found a bug?', 'wsklad' ); ?></h2>

<p>
	Прежде всего убедитесь, действительно ли была найдена ошибка и она до этого не была исправлена в обновлениях.
    Если ошибка исправлена в обновлениях, нужно просто установить исправленную версию.
</p>
<p>
    Перед сообщением об ошибке, требуется проверить:
</p>

<ul>
	<li>Правильно ли произведены настройки WordPress, WooCommerce, WSklad и расширений к ним.</li>
    <li>Используются ли совместимые версии WordPress, WooCommerce, WSklad и расширений к ним. Совместимость можно посмотреть в разделе <b>Окружения</b>.</li>
</ul>

<p>
    Если все настройки произведены верно и используются все совместимые продукты, но ошибка все равно присутствует - сообщите о ней.
</p>

<p>
	<a href="#" class="button button-primary">
		<?php _e( 'Report a bug', 'wsklad' ); ?>
	</a>
	<a href=" <?php echo admin_url( 'admin.php?page=wsklad_tools&section=environments' ); ?>" class="button">
		<?php _e( 'Environments', 'wsklad' ); ?>
	</a>
</p>