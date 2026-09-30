<?php
/**
 * Interface copy: old msgid => [new msgid, Russian for the new msgid].
 *
 * Only entries that change are listed. Anything absent keeps its existing translation, which
 * is deliberate: the Russian here was the source language and the English is a machine
 * translation of it, so most of the catalogue is already better than the string it translates.
 * Rewriting 356 strings that do not need rewriting would churn every msgid and lose the one
 * thing that is currently right about the localisation.
 *
 * Rules applied:
 *   - "MoySklad" becomes "Moy Sklad" everywhere; both spellings were in the tree.
 *   - Sentences that are not sentences get made into sentences.
 *   - Terminology is fixed once and used everywhere: Accounts, sync tasks, connection type,
 *     token, log files, the trash - not "basket", which is what "корзина" became once.
 *   - Placeholders (%s, %d, %1$s) and HTML are carried across untouched.
 *   - Buttons are verbs in the imperative, matching what WordPress does.
 */

return [

// --- src/Activation.php ---------------------------------------------------------------------
'WSKLAD successfully activated. You have made the right choice to integrate the site with Moy Sklad (plugin number one)!'
	=> ['WSKLAD is active. You have connected your site to Moy Sklad.', 'WSKLAD активирован. Ваш сайт подключён к Мой Склад.'],

'The basic plugin setup has not been done yet, so you can proceed to the setup, which takes no more than 5 minutes.'
	=> ['The initial setup has not been completed yet. It takes about five minutes.', 'Начальная настройка ещё не выполнена. Она занимает около пяти минут.'],

'Go to setting.'
	=> ['Go to settings', 'Перейти к настройкам'],

// --- src/Admin.php --------------------------------------------------------------------------
'Add accounts'
	=> ['Add accounts', 'Добавить учётные записи'],

'WSKLAD could not store its installation key (%s), so any Moy Sklad password or token saved now will be unreadable on the next page load. The plugin is otherwise working. This is almost always a permissions or disk-space problem with the wp_options table.'
	=> ['WSKLAD could not store its installation key (%s). Any Moy Sklad password or token saved from now on will be unreadable after the page reloads. Everything else keeps working. This is almost always a permissions or disk-space problem in the wp_options table.', 'WSKLAD не смог сохранить ключ установки (%s). Любой пароль или токен Мой Склад, сохранённый с этого момента, станет нечитаемым после перезагрузки страницы. Всё остальное продолжает работать. Почти всегда это проблема прав доступа или свободного места в таблице wp_options.'],

'WSKLAD could not load the libsodium PHP extension, so Moy Sklad passwords and tokens are currently stored unencrypted. Everything else works, but ask your host to enable ext-sodium — the plugin will then encrypt on the next save of each account.'
	=> ['WSKLAD could not load the libsodium PHP extension, so Moy Sklad passwords and tokens are stored unencrypted at the moment. Everything else works. Ask your host to enable ext-sodium and the plugin will encrypt each account the next time it is saved.', 'WSKLAD не смог загрузить PHP-расширение libsodium, поэтому пароли и токены Мой Склад сейчас хранятся без шифрования. Всё остальное работает. Попросите хостинг включить ext-sodium — и плагин зашифрует каждый аккаунт при следующем сохранении.'],

// --- src/Admin/Accounts/AllTable.php --------------------------------------------------------
'An initial setup is required.'
	=> ['Initial setup required.', 'Требуется начальная настройка.'],

'All account algorithms are active.'
	=> ['All sync tasks are running.', 'Все задачи синхронизации выполняются.'],

'All account algorithms are disabled.'
	=> ['All sync tasks are stopped.', 'Все задачи синхронизации остановлены.'],

'Data is being exchanged. Changing settings is not recommended.'
	=> ['Data exchange is in progress. Changing settings now is not recommended.', 'Идёт обмен данными. Менять настройки сейчас не рекомендуется.'],

'An error has occurred. You need to look at the event logs, they contain detailed information.'
	=> ['An error has occurred. The event log holds the details.', 'Произошла ошибка. Подробности — в журнале событий.'],

'Awaiting final removal. All algorithms are disabled.'
	=> ['Awaiting permanent deletion. All sync tasks are stopped.', 'Ожидает окончательного удаления. Все задачи синхронизации остановлены.'],

'Status description'
	=> ['Status', 'Статус'],

'User is not exists.'
	=> ['The user no longer exists.', 'Пользователь больше не существует.'],

'Connection type: '
	=> ['Connection type: ', 'Тип подключения: '],

// --- src/Admin/Accounts/Dashboard.php --------------------------------------------------------
'Updating the parameters of all basic settings, including data for authorization in Moy Sklad.'
	=> ['The basic settings, including the Moy Sklad credentials for this account.', 'Основные настройки, включая учётные данные для авторизации в Мой Склад.'],

'View and manage event logs for the current account.'
	=> ['View and manage the event log for this account.', 'Просмотр и управление журналом событий этого аккаунта.'],

'About account'
	=> ['Account details', 'Об аккаунте'],

// --- src/Admin/Accounts/Delete.php ----------------------------------------------------------
'The request to disconnect the account could not be verified. Please open the accounts list and try again.'
	=> ['The request to disconnect the account could not be verified. Open the accounts list and try again.', 'Не удалось проверить запрос на отключение аккаунта. Откройте список учётных записей и попробуйте снова.'],

'Error. The account to be deleted is active and cannot be deleted.'
	=> ['Error. This account is active, so it cannot be deleted. Disconnect it first.', 'Ошибка. Этот аккаунт активен, поэтому его нельзя удалить. Сначала отключите его.'],

'The account has been marked as deleted.'
	=> ['The account has been moved to the trash.', 'Аккаунт перемещён в корзину.'],

'The account has been successfully disconnected.'
	=> ['The account has been disconnected.', 'Аккаунт отключён.'],

'Deleting error. Please retry again.'
	=> ['Could not delete the account. Please try again.', 'Не удалось удалить аккаунт. Попробуйте снова.'],

// --- src/Admin/Accounts/DeleteForm.php -------------------------------------------------------
'I confirm that Account will be permanently and irrevocably deleted from WordPress.'
	=> ['I understand this account will be permanently deleted and cannot be restored.', 'Я понимаю, что эта учётная запись будет удалена безвозвратно и не подлежит восстановлению.'],

'The directory with files for account from the FILE system will be completely removed.'
	=> ['The account directory on the server will be deleted along with everything in it.', 'Каталог аккаунта на сервере будет удалён вместе со всем содержимым.'],

'Delete error. Please retry.'
	=> ['Could not delete the account. Please try again.', 'Не удалось удалить аккаунт. Попробуйте снова.'],

'Delete error. Confirmation of final deletion is required.'
	=> ['Confirm the permanent deletion to continue.', 'Подтвердите окончательное удаление, чтобы продолжить.'],

// --- src/Admin/Accounts/MainUpdate.php ------------------------------------------------------
'Account update success.'
	=> ['Account saved.', 'Аккаунт сохранён.'],

'Account update error. Please retry saving or change fields.'
	=> ['Could not save the account. Check the fields and try again.', 'Не удалось сохранить аккаунт. Проверьте поля и попробуйте снова.'],

'Authorization of requests for current account.'
	=> ['Used to authorise requests to Moy Sklad for this account.', 'Используется для авторизации запросов к Мой Склад от имени этого аккаунта.'],

'Used for authorization in Moy Sklad service.'
	=> ['Issued in your Moy Sklad account.', 'Выпускается в вашем аккаунте Мой Склад.'],

'Get it in account on the Moy Sklad. Leave the field empty to keep the stored token.'
	=> ['Issue one in your Moy Sklad account. Leave this field empty to keep the saved token.', 'Выпустите токен в аккаунте Мой Склад. Оставьте поле пустым, чтобы сохранить текущий токен.'],

'issuing a new token in Moy Sklad immediately invalidates the previous one. Any account still using the old token — here, on another site, or in another plugin — stops working at that moment.'
	=> ['issuing a new token in Moy Sklad immediately invalidates the previous one. Every account still using the old token — here, on another site, or in another plugin — stops working at that moment.', 'выпуск нового токена в Мой Склад немедленно аннулирует предыдущий. Все аккаунты, использующие старый токен — здесь, на другом сайте или в другом плагине, — перестанут работать в этот момент.'],

'Issue or revoke tokens at any time:'
	=> ['Issue or revoke tokens here at any time:', 'Выпуск и отзыв токенов — в любой момент здесь:'],

'Open Moy Sklad token settings'
	=> ['Open the Moy Sklad token settings', 'Открыть настройки токенов Мой Склад'],

'Login in Moy Sklad. After adding an account, changing the login is not possible.'
	=> ['The login for your Moy Sklad account. It cannot be changed once the account has been added.', 'Логин вашего аккаунта Мой Склад. Его нельзя изменить после добавления учётной записи.'],

'Password for the specified user Moy Sklad. Leave the field empty to keep the stored password.'
	=> ['The password for this Moy Sklad user. Leave the field empty to keep the saved password.', 'Пароль этого пользователя Мой Склад. Оставьте поле пустым, чтобы сохранить текущий пароль.'],

'Maintaining event logs for the current account. You can view the logs through the extension or via FTP.'
	=> ['The event log for this account. View it in the log viewer extension, or over FTP.', 'Журнал событий этого аккаунта. Его можно открыть расширением для просмотра логов или по FTP.'],

'Level for events'
	=> ['Event level', 'Уровень событий'],

'All events of the selected level will be recorded in the log file. The higher the level, the less data is recorded.'
	=> ['Events at the selected level and above are written to the log file. A higher level records less.', 'В файл журнала попадают события выбранного уровня и выше. Чем выше уровень, тем меньше данных записывается.'],

'Use level for main events'
	=> ['Use this level for main events', 'Использовать этот уровень для основных событий'],

'Maximum files'
	=> ['Number of files', 'Количество файлов'],

'Log files created daily. This option on the maximum number of stored files. By default saved of the logs are for the last 30 days.'
	=> ['A new log file is created each day. Older files are deleted once this number is reached; by default that is 30 days of history.', 'Новый файл журнала создаётся каждый день. Более старые удаляются, когда файлов становится больше указанного; по умолчанию хранится 30 дней истории.'],

'Other parameters'
	=> ['Advanced', 'Дополнительно'],

'Change of data processing behavior for environment compatibility and so on.'
	=> ['Limits applied to data processing for compatibility with your server.', 'Ограничения обработки данных, подобранные под ваш сервер.'],

'Maximum size of accepted requests'
	=> ['Maximum request size', 'Максимальный размер запроса'],

'Enter the maximum size of accepted requests from Moy Sklad at a time in bytes. May be specified with a dimension suffix, such as 7M, where M = megabyte, K = kilobyte, G - gigabyte.'
	=> ['The largest request the plugin will accept from Moy Sklad, in bytes. A suffix may be used, for example 7M — M for megabytes, K for kilobytes, G for gigabytes.', 'Максимальный размер запроса, который плагин примет от Мой Склад, в байтах. Можно указать суффикс: например, 7M — M для мегабайтов, K для килобайтов, G для гигабайтов.'],

'Current WSKLAD limit:'
	=> ['Current WSKLAD limit:', 'Текущее ограничение WSKLAD:'],

'Can only decrease the value, because it must not exceed the limits from the WSKLAD settings.'
	=> ['This value can only be lowered; it cannot exceed the limit from the WSKLAD settings.', 'Значение можно только уменьшить: оно не должно превышать ограничение из настроек WSKLAD.'],

'Maximum time for execution PHP'
	=> ['PHP execution time limit', 'Лимит времени выполнения PHP'],

'Value is seconds. Algorithms of current account will run until a time limit is end.'
	=> ['In seconds. This account\'s sync tasks stop when the limit is reached.', 'В секундах. Задачи синхронизации этого аккаунта остановятся по достижении лимита.'],

'If specify 0, the time limit will be disabled. Specifying 0 is not recommended, it is recommended not to exceed the WSKLAD limit.'
	=> ['A value of 0 removes the time limit. It is not recommended, and the WSKLAD limit should not be exceeded either.', 'Значение 0 отключает ограничение по времени. Это не рекомендуется, и превышать ограничение WSKLAD тоже не стоит.'],

'Debug is enabled!'
	=> ['Debug logging is on', 'Включён журнал отладки'],

'The current account has debug mode enabled. You must disable this mode after debugging is complete.'
	=> ['Debug logging is on for this account. Turn it off once you have finished debugging.', 'Для этого аккаунта включён журнал отладки. Выключите его, когда закончите отладку.'],

'Info is enabled!'
	=> ['Info logging is on', 'Включён журнал событий'],

'The extended information recording mode is enabled for the current account. It is recommended to disable this mode after debugging is complete.'
	=> ['Info logging is on for this account. Turn it off once you have finished debugging.', 'Для этого аккаунта включён журнал событий. Выключите его, когда закончите отладку.'],

'Slower authorization'
	=> ['Slower authorisation', 'Медленная авторизация'],

'This account connects by login and password. Since December 2026 Moy Sklad counts each such request as 4 units of the rate limit instead of 1, so this account can make roughly four times fewer requests per second. Switching to a permanent token restores the full rate.'
	=> ['This account connects with a login and password. From December 2026 Moy Sklad counts each such request as 4 units of the rate limit instead of 1, so this account can make about four times fewer requests per second. Switching to a token restores the full rate.', 'Этот аккаунт подключается по логину и паролю. С декабря 2026 года Мой Склад считает каждый такой запрос как 4 единицы лимита вместо 1, поэтому аккаунт может выполнять примерно вчетверо меньше запросов в секунду. Переход на токен возвращает полный лимит.'],

'You can issue a token at any time — the login keeps working until you switch.'
	=> ['You can issue a token at any time — the login and password keep working until you switch.', 'Вы можете выпустить токен в любой момент: логин и пароль продолжат работать, пока вы не переключитесь.'],

// --- src/Admin/Accounts/Update.php ----------------------------------------------------------
'Used for convenient distribution of multiple accounts.'
	=> ['A name of your choice, to tell your accounts apart.', 'Название на ваш усмотрение — чтобы различать учётные записи.'],

'Account name update success.'
	=> ['Account renamed.', 'Учётная запись переименована.'],

'Account name update error. Please retry saving or change fields.'
	=> ['Could not rename the account. Please try again.', 'Не удалось переименовать учётную запись. Попробуйте снова.'],

// --- src/Admin/Accounts/UpdateForm.php ------------------------------------------------------
'Check the box if you want to enable this account. Disabled by default.'
	=> ['Tick the box to enable this account. It is disabled by default.', 'Отметьте поле, чтобы включить эту учётную запись. По умолчанию она отключена.'],

'The account is either enabled or disabled. In the off state, all account mechanisms will not work.'
	=> ['An account is either enabled or disabled. While it is disabled, none of its sync tasks run.', 'Учётная запись либо включена, либо отключена. Пока она отключена, её задачи синхронизации не выполняются.'],

'Update error. Please retry.'
	=> ['Could not save. Please try again.', 'Не удалось сохранить. Попробуйте снова.'],

'Fast navigation'
	=> ['Quick links', 'Быстрые ссылки'],

// --- src/Admin/Accounts/Verification.php ----------------------------------------------------
'The account could not be verified: the request has expired or came from an external source. Use the Verification link in the accounts list.'
	=> ['The account could not be verified: the request has expired or came from somewhere else. Use the Verify link in the accounts list.', 'Не удалось проверить учётную запись: запрос истёк или пришёл из другого источника. Используйте ссылку «Проверить» в списке учётных записей.'],

'The account from Moy Sklad has been deleted. It is not possible to check the relevance.'
	=> ['The Moy Sklad account has been deleted, so it can no longer be verified.', 'Аккаунт в Мой Склад удалён, поэтому проверить его больше невозможно.'],

'Test connection is not success.'
	=> ['The connection test failed.', 'Проверка соединения не удалась.'],

// --- src/Admin/Add/ByLoginForm.php / ByTokenForm.php ----------------------------------------
'Connection to MoySklad by login and password.'
	=> ['Connect using a Moy Sklad login and password.', 'Подключение по логину и паролю от Мой Склад.'],

'This login is used to enter the MoySklad service.'
	=> ['The login you use to sign in to Moy Sklad.', 'Логин для входа в Мой Склад.'],

'Password from the entered login to enter the MoySklad service.'
	=> ['The password for that login.', 'Пароль от этого логина.'],

'Connection to MoySklad using a token generated on the MoySklad side.'
	=> ['Connect using a token you issue in Moy Sklad.', 'Подключение по токену, выпущенному в Мой Склад.'],

'The token can be generated in MoySklad. After generating it, you must enter it and click on the button for connection.'
	=> ['Issue a token in your Moy Sklad account, paste it here, then click Connect.', 'Выпустите токен в аккаунте Мой Склад, вставьте его сюда и нажмите «Подключить».'],

'issuing a new token in Moy Sklad immediately invalidates the previous one. If you replace the token here, every account still using the old token stops working — including other plugins and other sites of yours.'
	=> ['issuing a new token in Moy Sklad immediately invalidates the previous one. Replacing the token here stops every account still using the old one — including your other plugins and your other sites.', 'выпуск нового токена в Мой Склад немедленно аннулирует предыдущий. Если заменить токен здесь, работа всех аккаунтов на старом токене прекратится — включая ваши другие плагины и другие сайты.'],

// --- src/Admin/Add/Form.php -----------------------------------------------------------------
'An arbitrary name for the connection. Used for reference purposes.'
	=> ['A name of your choice, to tell this connection apart from others.', 'Название на ваш усмотрение — чтобы отличать это подключение от других.'],

'Test connection before adding?'
	=> ['Test the connection before adding the account?', 'Проверять соединение перед добавлением учётной записи?'],

'Connection error. Please retry.'
	=> ['Connection error. Please try again.', 'Ошибка соединения. Попробуйте снова.'],

'Account connection error. Name is required.'
	=> ['Enter a name for the account.', 'Введите название учётной записи.'],

'Account connection error. Login is required.'
	=> ['Enter a login.', 'Введите логин.'],

'Account connection error. Password is required.'
	=> ['Enter a password.', 'Введите пароль.'],

'Account connection error. Token is required.'
	=> ['Enter a token.', 'Введите токен.'],

'Account connection error. Name is exists.'
	=> ['An account with this name already exists.', 'Учётная запись с таким названием уже есть.'],

'Account connection error. Login is exists.'
	=> ['An account with this login already exists.', 'Учётная запись с таким логином уже есть.'],

'Account connection error. Token is exists.'
	=> ['This token is already in use.', 'Этот токен уже используется.'],

'Account connection error. Test connection is not success.'
	=> ['The connection test failed.', 'Проверка соединения не удалась.'],

'Account connection success. Account connection id:'
	=> ['Account connected.', 'Учётная запись подключена.'],

'Account connection error. Please retry saving or change fields.'
	=> ['Could not connect the account. Check the fields and try again.', 'Не удалось подключить учётную запись. Проверьте поля и попробуйте снова.'],

// --- src/Admin/Settings/Form.php ------------------------------------------------------------
'Save error. Please retry.'
	=> ['Could not save the settings. Please try again.', 'Не удалось сохранить настройки. Попробуйте снова.'],

'Save success.'
	=> ['Settings saved.', 'Настройки сохранены.'],

// --- src/Admin/Settings/LogsForm.php --------------------------------------------------------
'Level for main events'
	=> ['Main event level', 'Уровень основных событий'],

'All events of the selected level will be recorded the accounts events in the log file. The higher the level, the less data is recorded.'
	=> ['Events at the selected level and above are written to the account log. A higher level records less.', 'В журнал учётных записей попадают события выбранного уровня и выше. Чем выше уровень, тем меньше данных записывается.'],

'All events of the selected level will be recorded the tools events in the log file. The higher the level, the less data is recorded.'
	=> ['Events at the selected level and above are written to the tools log. A higher level records less.', 'В журнал инструментов попадают события выбранного уровня и выше. Чем выше уровень, тем меньше данных записывается.'],

'Levels by context'
	=> ['Level per context', 'Уровень по разделам'],

'Event log settings based on context.'
	=> ['Set the event level separately for each context.', 'Уровень событий задаётся отдельно для каждого раздела.'],

// --- src/Admin/Settings/MainForm.php --------------------------------------------------------
'API MoySklad'
	=> ['Moy Sklad API', 'API Мой Склад'],

'Used for API connections.'
	=> ['Connection to the Moy Sklad API.', 'Подключение к API Мой Склад.'],

'This host is used for API connection. If the host is unknown, use the value: api.moysklad.ru'
	=> ['The host the plugin connects to. Unless you know otherwise, leave this as api.moysklad.ru.', 'Хост, к которому подключается плагин. Если у вас нет причин его менять, оставьте api.moysklad.ru.'],

'Enable HTTPS enforcement for requests to the MoySklad API?'
	=> ['Require HTTPS for API requests?', 'Требовать HTTPS для запросов к API?'],

'If enabled, all API requests from the site to MoySklad will be made over the secure HTTPS protocol.'
	=> ['When on, every request from the site to Moy Sklad goes over HTTPS.', 'Если включено, все запросы с сайта к Мой Склад выполняются по защищённому протоколу HTTPS.'],

'Used to control what extensions may add to the plugin.'
	=> ['Controls which installed extensions may add their own screens.', 'Определяет, какие установленные расширения могут добавлять свои экраны.'],

'Allow extensions to add their own features?'
	=> ['Let extensions add their own screens?', 'Разрешить расширениям добавлять свои экраны?'],

'If enabled, extensions connected to the "wsklad_extensions_loading" filter can add their features to the plugin. If disabled, the filter is not applied and only the extensions installed with the plugin are available.'
	=> ['When on, extensions hooked into the "wsklad_extensions_loading" filter can add their own screens. When off, the filter is not applied and only the extensions that came with the plugin are shown.', 'Если включено, расширения, подключённые к фильтру «wsklad_extensions_loading», могут добавлять свои экраны. Если отключено, фильтр не применяется и показываются только расширения, установленные вместе с плагином.'],

'Allow extensions to add their own tools?'
	=> ['Let extensions add their own tools?', 'Разрешить расширениям добавлять свои инструменты?'],

'If enabled, extensions connected to the "wsklad_load_tools" filter can add their tools to the Moy Sklad section. If disabled, the filter is not applied and only the tools shipped with the plugin are available.'
	=> ['When on, extensions hooked into the "wsklad_load_tools" filter can add their own tools to the Moy Sklad section. When off, the filter is not applied and only the tools that came with the plugin are shown.', 'Если включено, расширения, подключённые к фильтру «wsklad_load_tools», могут добавлять свои инструменты в раздел Мой Склад. Если отключено, фильтр не применяются и доступны только инструменты, поставляемые с плагином.'],

'Some settings for the accounts.'
	=> ['How accounts behave.', 'Поведение учётных записей.'],

'Test connection before add'
	=> ['Test the connection first', 'Сначала проверять соединение'],

'Enable data validation to connect to Moy Sklad before adding?'
	=> ['Verify each account against Moy Sklad when it is added?', 'Проверять каждую учётную запись в Мой Склад при добавлении?'],

'If enabled, then when connecting accounts from Moy Sklad, they will be checked for validity by a test connection.'
	=> ['When on, each account is verified with a test request as soon as it is added.', 'Если включено, каждая учётная запись проверяется тестовым запросом сразу после добавления.'],

'Unique names'
	=> ['Unique account names', 'Уникальные названия'],

'Require unique names for accounts?'
	=> ['Require a different name for every account?', 'Требовать уникальное название для каждой учётной записи?'],

'If enabled, will need to provide unique names for the accounts.'
	=> ['When on, two accounts cannot share a name.', 'Если включено, две учётные записи не могут называться одинаково.'],

'Number in the list'
	=> ['Accounts per page', 'Записей на странице'],

'The number of displayed accounts on one page.'
	=> ['How many accounts to show on one page.', 'Сколько учётных записей показывать на одной странице.'],

'Deleting drafts without trash'
	=> ['Skip the trash for drafts', 'Удалять черновики без корзины'],

'Enable deleting drafts without placing them in the trash?'
	=> ['Delete draft accounts permanently, without moving them to the trash?', 'Удалять черновики сразу, без помещения в корзину?'],

'If enabled, accounts for connections in the draft status will be deleted without being added to the basket.'
	=> ['When on, deleting a draft account removes it permanently instead of moving it to the trash.', 'Если включено, удаление черновика происходит сразу, без помещения в корзину.'],

'Used to set up the environment.'
	=> ['Limits that depend on what your server allows.', 'Ограничения, зависящие от настроек вашего сервера.'],

'Value is seconds. wsklad will run until a time limit is set.'
	=> ['In seconds. WSKLAD stops at the time limit.', 'В секундах. WSKLAD останавливается по достижении лимита.'],

'Server value:'
	=> ['Server limit:', 'Ограничение сервера:'],

'If specify 0, the time limit will be disabled. Specifying 0 is not recommended, it is recommended not to exceed the server limit.'
	=> ['A value of 0 removes the time limit. It is not recommended, and the server limit should not be exceeded either.', 'Значение 0 отключает ограничение по времени. Это не рекомендуется, и превышать ограничение сервера тоже не стоит.'],

'Maximum request size'
	=> ['Maximum request size', 'Максимальный размер запроса'],

'The setting must not take a size larger than specified in the server settings.'
	=> ['This must not exceed the limit set on the server.', 'Это значение не должно превышать ограничение, заданное на сервере.'],

// --- src/Admin/Wizards/Setup/Database.php ---------------------------------------------------
'Create tables error. Please retry.'
	=> ['Could not create the database tables. Please try again.', 'Не удалось создать таблицы базы данных. Попробуйте снова.'],

// --- src/Core.php ---------------------------------------------------------------------------
'Timer not loaded.'
	=> ['The timer did not load.', 'Не удалось загрузить таймер.'],

'Extensions not loaded.'
	=> ['The extensions did not load.', 'Не удалось загрузить расширения.'],

'Extensions not initialized.'
	=> ['The extensions were not initialised.', 'Расширения не были инициализированы.'],

'Tools not loaded.'
	=> ['The tools did not load.', 'Не удалось загрузить инструменты.'],

'Tools not initialized.'
	=> ['The tools were not initialised.', 'Инструменты не были инициализированы.'],

'WSKLAD recreated the database tables that were missing: %s. If you removed them on purpose, ignore this notice.'
	=> ['WSKLAD recreated the missing database tables: %s. If you removed them yourself, you can ignore this notice.', 'WSKLAD восстановил отсутствовавшие таблицы базы данных: %s. Если вы удалили их сами, это уведомление можно проигнорировать.'],

'WSKLAD database tables were missing and have just been recreated. If you deleted the plugin options on purpose, ignore this notice.'
	=> ['The WSKLAD database tables were missing and have just been recreated. If you deleted the plugin options yourself, you can ignore this notice.', 'Таблицы базы данных WSKLAD отсутствовали и только что восстановлены. Если вы сами удалили настройки плагина, это уведомление можно проигнорировать.'],

'WSKLAD database tables are missing and could not be recreated automatically. Deactivate and activate the plugin; if that does not help, check the file permissions of wp-content.'
	=> ['The WSKLAD database tables are missing and could not be recreated. Deactivate and reactivate the plugin; if that does not help, check the file permissions in wp-content.', 'Таблицы базы данных WSKLAD отсутствуют, и восстановить их автоматически не удалось. Деактивируйте и снова активируйте плагин; если не поможет, проверьте права доступа к файлам в wp-content.'],

'Localization loaded.'
	=> ['The translation was loaded.', 'Перевод загружен.'],

// --- src/Data/Storages/AccountsStorage.php ---------------------------------------------------
'Account could not insert into the database.'
	=> ['The account could not be saved to the database.', 'Не удалось сохранить учётную запись в базе данных.'],

'Unknown column in accounts query: %s'
	=> ['Unknown column in the accounts query: %s', 'Неизвестная колонка в запросе к учётным записям: %s'],

// --- src/Extensions -------------------------------------------------------------------------
'Meta value by name is not available.'
	=> ['No meta value by that name.', 'Значение с таким именем отсутствует.'],

'Set $extensions is not valid.'
	=> ['The $extensions property is not valid.', 'Свойство $extensions задано неверно.'],

'Extension not found by id.'
	=> ['No extension with that id.', 'Расширение с таким идентификатором не найдено.'],

'Extension is not implementation ExtensionContract. Skipped init.'
	=> ['The extension does not implement ExtensionContract, so it was skipped.', 'Расширение не реализует ExtensionContract, поэтому оно пропущено.'],

'Init extension exception:'
	=> ['Exception while initialising the extension:', 'Исключение при инициализации расширения:'],

'Get extension by id is unavailable.'
	=> ['Looking an extension up by id is unavailable.', 'Поиск расширения по идентификатору недоступен.'],

'Extensions load exception:'
	=> ['Exception while loading the extensions:', 'Исключение при загрузке расширений:'],

// --- src/Privacy/Privacy.php ----------------------------------------------------------------
'Moy Sklad account connections'
	=> ['Moy Sklad account connections', 'Подключения к Мой Склад'],

'No WSKLAD data is associated with this address.'
	=> ['This email address is not associated with any WSKLAD data.', 'Этот адрес электронной почты не связан с данными WSKLAD.'],

'WSKLAD deactivated %d Moy Sklad connection. Re-enter the credential in Moy Sklad to revoke it there.'
	=> [
		'WSKLAD deactivated %d Moy Sklad connection. Revoke the credential in Moy Sklad to remove it there.',
		[
			'WSKLAD отключил %d подключение к Мой Склад. Чтобы отозвать учётные данные в Мой Склад, войдите туда заново.',
			'WSKLAD отключил %d подключения к Мой Склад. Чтобы отозвать учётные данные в Мой Склад, войдите туда заново.',
			'WSKLAD отключил %d подключений к Мой Склад. Чтобы отозвать учётные данные в Мой Склад, войдите туда заново.',
		],
	],

'Customer personal data held in Moy Sklad is not affected: revoke it in Moy Sklad. Files already downloaded into wp-content/uploads are not affected either; delete them separately.'
	=> ['Personal data held in Moy Sklad is left untouched: revoke it there. Files already downloaded to wp-content/uploads are left untouched too — delete those separately.', 'Персональные данные, хранящиеся в Мой Склад, не затрагиваются: отзовите их там же. Файлы, уже скачанные в wp-content/uploads, тоже не затрагиваются — удалите их отдельно.'],

'WSKLAD could not deactivate the connection "%s".'
	=> ['WSKLAD could not disconnect "%s".', 'WSKLAD не смог отключить подключение «%s».'],

// --- src/Tools/Environments/Init.php --------------------------------------------------------
'Data about all current environments.'
	=> ['Everything WSKLAD can see about your setup.', 'Всё, что WSKLAD знает о вашей конфигурации.'],

'WordPress environment'
	=> ['WordPress', 'WordPress'],

'WSKLAD environment'
	=> ['WSKLAD', 'WSKLAD'],

'WooCommerce environment'
	=> ['WooCommerce', 'WooCommerce'],

'Server environment'
	=> ['Server', 'Сервер'],

'WordPress multisite'
	=> ['WordPress multisite', 'Мультисайт WordPress'],

'WordPress debug'
	=> ['WordPress debug mode', 'Режим отладки WordPress'],

'WordPress cron'
	=> ['WordPress cron', 'Планировщик WordPress'],

'WordPress memory limit'
	=> ['WordPress memory limit', 'Лимит памяти WordPress'],

'Suhosin'
	=> ['Suhosin', 'Suhosin'],

'Fsockopen or curl enabled'
	=> ['FSockopen or cURL available', 'Доступен FSockopen или cURL'],

'PHP soapclient enabled'
	=> ['PHP SoapClient available', 'Доступен PHP SoapClient'],

'PHP domdocument enabled'
	=> ['PHP DOMDocument available', 'Доступен PHP DOMDocument'],

'PHP gzip enabled'
	=> ['PHP gzip available', 'Доступен PHP gzip'],

'PHP mbstring enabled'
	=> ['PHP mbstring available', 'Доступен PHP mbstring'],

'unknown (WooCommerce not active)'
	=> ['unknown (WooCommerce is not active)', 'неизвестно (WooCommerce неактивен)'],

'unknown (WooCommerce is too old to report order storage)'
	=> ['unknown (this version of WooCommerce cannot report where orders are stored)', 'неизвестно (эта версия WooCommerce не сообщает, где хранятся заказы)'],

'no OrderUtil and no woocommerce_custom_orders_table_enabled option'
	=> ['neither OrderUtil nor the woocommerce_custom_orders_table_enabled option', 'ни OrderUtil, ни опции woocommerce_custom_orders_table_enabled'],

'Order storage'
	=> ['Where orders are stored', 'Где хранятся заказы'],

'Are orders stored in the posts tables or in HPOS? (WooCommerce 8.2+ calls this High-Performance Order Storage.)'
	=> ['Whether WooCommerce keeps orders in the posts tables or in HPOS — High-Performance Order Storage, the name WooCommerce 8.2+ uses for its own order tables.', 'Хранит ли WooCommerce заказы в таблицах записей или в HPOS — High-Performance Order Storage, так WooCommerce 8.2+ называет собственные таблицы заказов.'],

'Orders table'
	=> ['Orders table', 'Таблица заказов'],

'The actual table an order is a row in. Compare this name against a SQL log before concluding anything.'
	=> ['The table an order is really a row in. Compare this against a SQL log before drawing a conclusion.', 'Таблица, в которой заказ действительно хранится строкой. Сверьте её с логом SQL, прежде чем делать вывод.'],

'Order storage detected via'
	=> ['Detected from', 'Определено по'],

'Which signal answered the question. An empty answer means nothing could answer it.'
	=> ['The signal that answered. An empty value means nothing could.', 'Признак, который дал ответ. Пустое значение означает, что ответить было нечем.'],

'not active'
	=> ['not active', 'неактивно'],

// --- src/Traits/AccountsUtilityTrait.php ----------------------------------------------------
'Undefined'
	=> ['Undefined', 'Не определено'],

'by Token'
	=> ['by token', 'по токену'],

'by Login & Password'
	=> ['by login and password', 'по логину и паролю'],

// --- src/Traits/DatetimeUtilityTrait.php -----------------------------------------------------
'not'
	=> ['not', 'нет'],

'in'
	=> ['in', 'через'],

// --- views ---------------------------------------------------------------------------------
'ID of the account to be deleted:'
	=> ['Account ID:', 'ID учётной записи:'],

'Name of the account to be deleted:'
	=> ['Account name:', 'Название учётной записи:'],

'Path of the account directory to be deleted:'
	=> ['Account directory:', 'Каталог учётной записи:'],

'Error. Delete is not available'
	=> ['Error. Deletion is unavailable.', 'Ошибка. Удаление недоступно.'],

'Accounts by query is not found, query:'
	=> ['No accounts matched this search:', 'По этому запросу ничего не найдено:'],

'Accounts not found.'
	=> ['No accounts found.', 'Учётные записи не найдены.'],

'To continue working, you must add at least one account from Moy Sklad.'
	=> ['Add at least one Moy Sklad account to continue.', 'Чтобы продолжить, добавьте хотя бы одну учётную запись Мой Склад.'],

'Error. Page for accounts not found.'
	=> ['Error. The accounts screen is missing.', 'Ошибка. Экран учётных записей не найден.'],

'Description is not exists.'
	=> ['No description.', 'Нет описания.'],

'Back to accounts list'
	=> ['Back to the accounts list', 'Вернуться к списку учётных записей'],

'Update is not available. Account not found or unavailable.'
	=> ['This account cannot be edited — it was not found.', 'Эту учётную запись нельзя изменить — она не найдена.'],

'Save account'
	=> ['Save account', 'Сохранить учётную запись'],

'Error. Page for add accounts not found.'
	=> ['Error. The add-account screen is missing.', 'Ошибка. Экран добавления учётной записи не найден.'],

'Enter a name for the new account and click the add account button.'
	=> ['Give the new account a name, then click Add account.', 'Введите название новой учётной записи и нажмите «Добавить».'],

'Error. Page not found.'
	=> ['Error. Page not found.', 'Ошибка. Страница не найдена.'],

'Extensions not found.'
	=> ['No extensions found.', 'Расширения не найдены.'],

'As soon as the extensions are installed, they will appear in this section.'
	=> ['Installed extensions appear here.', 'Установленные расширения появятся здесь.'],

'Information about all available official extensions is available on the website:'
	=> ['All official extensions are listed on our website:', 'Все доступные официальные расширения перечислены на сайте:'],

'Error. Page for extensions not found.'
	=> ['Error. The extensions screen is missing.', 'Ошибка. Экран расширений не найден.'],

'Versions WSKLAD:'
	=> ['WSKLAD versions:', 'Версии WSKLAD:'],

'Error. Page for settings not found.'
	=> ['Error. The settings screen is missing.', 'Ошибка. Экран настроек не найден.'],

'Save settings'
	=> ['Save settings', 'Сохранить настройки'],

'Error. Step not found or unavailable.'
	=> ['Error. This step is missing.', 'Ошибка. Этот шаг не найден.'],

'Master of install and update'
	=> ['Installation and updates', 'Установка и обновления'],

// --- views/helps/bugs.php -------------------------------------------------------------------
'First of all, you need to make sure that a bug has been found and that it has not been fixed in updates before.'
	=> ['First, make sure it is a bug, and that a recent update has not already fixed it.', 'Сначала убедитесь, что это действительно ошибка и что её не исправили в одном из обновлений.'],

'If the bug is fixed in the updates, you just need to install the corrected version.'
	=> ['If an update fixes it, installing that version is all you need to do.', 'Если ошибка исправлена в обновлении, достаточно установить эту версию.'],

'Before reporting an error need to check:'
	=> ['Before reporting a bug, please check:', 'Перед отчётом об ошибке проверьте:'],

'Whether the settings for WordPress, WSKLAD and their extensions are correct.'
	=> ['That the WordPress, WSKLAD and extension settings are correct.', 'Что настройки WordPress, WSKLAD и расширений заданы верно.'],

'Whether compatible versions of WordPress, WSKLAD and their extensions are used. Compatibility can be found in the Environments section.'
	=> ['That WordPress, WSKLAD and the extensions are versions that work together — the Environments screen lists what is compatible.', 'Что версии WordPress, WSKLAD и расширений совместимы между собой — список совместимого есть на экране «Окружение».'],

'If all settings are made correctly and compatible products of the latest versions are used, but the error is still present, you must report it.'
	=> ['If the settings are right, everything is up to date and the bug is still there, please report it.', 'Если настройки верны, всё обновлено, а ошибка осталась — пожалуйста, сообщите о ней.'],

'Report a bug using the methods available to you. When reporting a bug, you must have a valid technical support code for the project on which the bug occurred.'
	=> ['Report the bug using whichever channel you have. You will need a valid technical support code for the project the bug happened in.', 'Сообщите об ошибке любым доступным способом. Понадобится действующий код техподдержки для проекта, в котором возникла ошибка.'],

// --- views/helps/features.php ---------------------------------------------------------------
'First of all, you need to make sure - whether the necessary opportunity is really missing.'
	=> ['First, make sure the feature is really missing.', 'Сначала убедитесь, что такой возможности действительно нет.'],

'It may be worth looking at the available settings or reading the documentation.'
	=> ['It may already be in the settings, or in the documentation.', 'Возможно, она уже есть в настройках или описана в документации.'],

'Also, before requesting an opportunity, you need to make sure that:'
	=> ['Before requesting a feature, please check:', 'Перед запросом возможности проверьте:'],

'Is the required feature added in WSKLAD updates.'
	=> ['Whether a WSKLAD update already added it.', 'Не добавили ли её в одном из обновлений WSKLAD.'],

'Whether the possibility is implemented by an additional extension to WSKLAD.'
	=> ['Whether an extension provides it instead.', 'Не реализована ли она отдельным расширением WSKLAD.'],

'Whether the desired opportunity is waiting for its implementation.'
	=> ['Whether it is already on the roadmap.', 'Не стоит ли она в плане развития.'],

'If the feature is added in WSKLAD updates, you just need to install the updated version.'
	=> ['If an update added it, install that version.', 'Если возможность появилась в обновлении, установите его.'],

'But if the feature is implemented in an extension to WSKLAD, then this feature should not be expected as part of WSKLAD and you need to install the extension.'
	=> ['If it lives in an extension, it will not arrive in WSKLAD itself — install the extension.', 'Если возможность реализована в расширении, в сам WSKLAD она не появится — установите расширение.'],

'Because the feature implemented in the extension is so significant that it needed to create an extension for it.'
	=> ['Some features are substantial enough to live in an extension of their own.', 'Некоторые возможности достаточно объёмны, чтобы жить в отдельном расширении.'],

// --- views/helps/main.php -------------------------------------------------------------------
'If no understand how Integration with Moy Sklad works, how to use and supplement it, can view the documentation.'
	=> ['If you are not sure how the Moy Sklad integration works or what you can do with it, start with the documentation.', 'Если непонятно, как работает интеграция с Мой Склад и что в ней можно настроить, начните с документации.'],

'Documentation contains all kinds of resources such as code snippets, user guides and more.'
	=> ['The documentation holds user guides, code examples and more.', 'В документации есть руководства, примеры кода и многое другое.'],

// --- views/promo/logs.php -------------------------------------------------------------------
'Viewing logs is possible with the extension installed. The logs contain operation information and error information.'
	=> ['Install the log viewer extension to read the logs. They record what the plugin did and what went wrong.', 'Чтобы читать журналы, установите расширение для их просмотра. В них записано, что делал плагин и где возникли ошибки.'],

'If something does not work, or it is not clear how it works, look at the logs. Without a log viewer extension, they can be viewed via FTP.'
	=> ['When something misbehaves, the logs are the first place to look. Without the extension you can open them over FTP.', 'Когда что-то работает не так, первым делом стоит посмотреть журналы. Без расширения их можно открыть по FTP.'],

'After installing the extension, this section will be filled with extension features for viewing logs.'
	=> ['Once the extension is installed, it will show its log viewer here.', 'После установки расширения здесь появится его просмотрщик журналов.'],

// --- views/wizards/steps/check.php ----------------------------------------------------------
'Welcome to WSKLAD!'
	=> ['Welcome to WSKLAD', 'Добро пожаловать в WSKLAD'],

'Thank you for choosing WSKLAD to website! This is only complete solution for integrating WordPress with Moy Sklad.'
	=> ['Thank you for choosing WSKLAD — a complete integration between WordPress and Moy Sklad.', 'Спасибо, что выбрали WSKLAD — полноценную интеграцию WordPress с Мой Склад.'],

'This quick setup wizard will help you configure the basic settings.'
	=> ['This wizard sets up the essentials. It takes about five minutes.', 'Этот мастер настроит основные параметры. Это займёт около пяти минут.'],

'PHP scripts execution time is less than 10 seconds. WSKLAD requires at least 20. Set php_max_execution_time to more than 20 seconds.'
	=> ['PHP is allowed to run for less than 10 seconds. WSKLAD needs at least 20 — raise php_max_execution_time to 20 or more.', 'PHP выполняется меньше 10 секунд. WSKLAD требует не менее 20 — увеличьте php_max_execution_time до 20 и более.'],

'Its should not take longer than five minutes.'
	=> ['It should take no more than five minutes.', 'Это займёт не более пяти минут.'],

'Lets Go!'
	=> ['Start setup', 'Начать настройку'],

'Need to fix the compatibility errors and return to the setup wizard.'
	=> ['Fix the compatibility errors to carry on.', 'Устраните ошибки совместимости, чтобы продолжить.'],

// --- views/wizards/steps/complete.php ------------------------------------------------------
'Installation completed!'
	=> ['Setup complete', 'Настройка завершена'],

'Now you can proceed to using the WSKLAD plugin.'
	=> ['WSKLAD is ready to use.', 'WSKLAD готов к работе.'],

'Go to use'
	=> ['Start using WSKLAD', 'Начать работу'],

// --- views/wizards/steps/database.php ------------------------------------------------------
'Creating tables in the database'
	=> ['Creating the database tables', 'Создание таблиц базы данных'],

'If continue, the required tables will be created in the database.'
	=> ['Continue to create the tables WSKLAD needs.', 'Продолжите, чтобы создать таблицы, необходимые WSKLAD.'],

];
