<?php

/**
 * Русские переводы для плагина FreeKassa
 */

return [
    // Общие
    'freekassa' => 'FreeKassa',
    'freekassa_payment_system' => 'Платежная система FreeKassa',
    'freekassa_description' => 'Прием платежей через FreeKassa с поддержкой множественных магазинов и валют',
    'plugin_enabled' => 'Включить плагин',
    'plugin_disabled' => 'Платежная система временно отключена',
    'settings_saved' => 'Настройки успешно сохранены',
    
    // Административная панель
    'admin_panel' => 'Панель управления FreeKassa',
    'plugin_settings' => 'Настройки плагина',
    'instances_management' => 'Управление магазина',
    'transaction_history' => 'История транзакций',
    'api_settings' => 'Настройки API',
    'statistics' => 'Статистика',
    
    // Управление магазинами (Instances)
    'instances' => 'Магазины',
    'add_instance' => 'Добавить магазин',
    'edit_instance' => 'Редактировать магазин',
    'delete_instance' => 'Удалить магазин',
    'instance_name' => 'Название магазина',
    'instance_description' => 'Описание магазина',
    'no_instances' => 'Магазины не созданы',
    'instance_created' => 'Магазин успешно создан',
    'instance_updated' => 'Магазин успешно обновлен',
    'instance_deleted' => 'Магазин успешно удален',
    'confirm_delete_instance' => 'Вы уверены, что хотите удалить этот магазин?',
    
    // Настройки магазина
    'shop_id' => 'ID магазина',
    'shop_id_placeholder' => 'Введите ID магазина FreeKassa',
    'shop_id_hint' => 'ID вашего магазина из личного кабинета FreeKassa',
    'api_key' => 'API ключ',
    'api_key_placeholder' => 'Введите API ключ',
    'api_key_hint' => 'API ключ из настроек магазина FreeKassa',
    'secret_word' => 'Секретное слово',
    'secret_word_placeholder' => 'Секретное слово для подписи формы',
    'secret_word_hint' => 'Секретное слово из настроек магазина (для формы оплаты)',
    'secret_word_2' => 'Секретное слово 2',
    'secret_word_2_placeholder' => 'Секретное слово для проверки webhook',
    'secret_word_2_hint' => 'Секретное слово 2 из настроек магазина (для проверки уведомлений)',
    
    // Валюты и способы оплаты
    'currency' => 'Валюта',
    'currency_default' => 'Валюта по умолчанию',
    'payment_method' => 'Способ оплаты',
    'payment_method_default' => 'Способ оплаты по умолчанию',
    'select_currency' => 'Выберите валюту',
    'select_payment_method' => 'Выберите способ оплаты',
    'available_currencies' => 'Доступные валюты',
    'available_payment_methods' => 'Доступные способы оплаты',
    'refresh_currencies' => 'Обновить список валют',
    'currencies_updated' => 'Список валют успешно обновлен',
    'limits' => 'Лимиты',
    'fee' => 'Комиссия',
    
    // Валюты
    'RUB' => 'Российский рубль',
    'USD' => 'Доллар США',
    'EUR' => 'Евро',
    'UAH' => 'Украинская гривна',
    'KZT' => 'Казахстанский тенге',
    
    // Способы оплаты (согласно API FreeKassa)
    'payment_method_1' => 'FK WALLET RUB',
    'payment_method_2' => 'FK WALLET USD',
    'payment_method_3' => 'FK WALLET EUR',
    'payment_method_4' => 'VISA RUB',
    'payment_method_6' => 'Yoomoney',
    'payment_method_7' => 'VISA UAH',
    'payment_method_8' => 'MasterCard RUB',
    'payment_method_9' => 'MasterCard UAH',
    'payment_method_10' => 'Qiwi',
    'payment_method_11' => 'VISA EUR',
    'payment_method_12' => 'МИР',
    'payment_method_13' => 'Онлайн банк',
    'payment_method_14' => 'USDT (ERC20)',
    'payment_method_15' => 'USDT (TRC20)',
    'payment_method_16' => 'Bitcoin Cash',
    'payment_method_17' => 'BNB',
    'payment_method_18' => 'DASH',
    'payment_method_19' => 'Dogecoin',
    'payment_method_20' => 'ZCash',
    'payment_method_21' => 'Monero',
    'payment_method_22' => 'Waves',
    'payment_method_23' => 'Ripple',
    'payment_method_24' => 'Bitcoin',
    'payment_method_25' => 'Litecoin',
    'payment_method_26' => 'Ethereum',
    'payment_method_27' => 'SteamPay',
    'payment_method_28' => 'Мегафон',
    'payment_method_32' => 'VISA USD',
    'payment_method_33' => 'Perfect Money USD',
    'payment_method_34' => 'Shiba Inu',
    'payment_method_35' => 'QIWI API',
    'payment_method_36' => 'Card RUB API',
    'payment_method_37' => 'Google pay',
    'payment_method_38' => 'Apple pay',
    'payment_method_39' => 'Tron',
    'payment_method_40' => 'Webmoney WMZ',
    'payment_method_41' => 'VISA / MasterCard KZT',
    'payment_method_42' => 'СБП',
    'payment_method_44' => 'СБП (API)',
    
    // Статусы
    'enabled' => 'Включено',
    'disabled' => 'Отключено',
    'status' => 'Статус',
    'test_mode' => 'Тестовый режим',
    'test_mode_hint' => 'В тестовом режиме платежи не проводятся реально',
    'active' => 'Активно',
    'inactive' => 'Неактивно',
    
    // Пользовательская часть
    'payment_title' => 'Пополнение баланса через FreeKassa',
    'payment_subtitle' => 'Выберите способ оплаты и введите сумму',
    'select_shop' => 'Выберите магазин',
    'amount' => 'Сумма',
    'amount_placeholder' => 'Введите сумму платежа',
    'amount_hint' => 'Минимальная сумма: {min}, Максимальная: {max}',
    'min_amount' => 'Минимальная сумма',
    'max_amount' => 'Максимальная сумма',
    'email' => 'Email',
    'email_placeholder' => 'Введите ваш email',
    'phone' => 'Телефон',
    'phone_placeholder' => 'Введите ваш телефон (опционально)',
    'pay_button' => 'Перейти к оплате',
    'creating_payment' => 'Создание платежа...',
    'payment_created' => 'Платеж создан! Переход на страницу оплаты...',
    
    // Транзакции
    'transactions' => 'Транзакции',
    'transaction_id' => 'ID транзакции',
    'order_id' => 'ID заказа',
    'payment_id' => 'ID платежа',
    'user' => 'Пользователь',
    'date' => 'Дата',
    'no_transactions' => 'Транзакции отсутствуют',
    
    // Статусы транзакций
    'status_pending' => 'Ожидание оплаты',
    'status_completed' => 'Оплачено',
    'status_failed' => 'Ошибка',
    'status_refunded' => 'Возврат',
    'status_cancelled' => 'Отменено',
    
    // Действия
    'save' => 'Сохранить',
    'cancel' => 'Отмена',
    'delete' => 'Удалить',
    'edit' => 'Редактировать',
    'view' => 'Просмотр',
    'close' => 'Закрыть',
    'back' => 'Назад',
    'refresh' => 'Обновить',
    'search' => 'Поиск',
    'filter' => 'Фильтр',
    'export' => 'Экспорт',
    'action' => 'Действие',
    'actions' => 'Действия',
    
    // Статистика
    'total_transactions' => 'Всего транзакций',
    'successful_payments' => 'Успешных платежей',
    'total_amount' => 'Общая сумма',
    'today' => 'Сегодня',
    'this_week' => 'На этой неделе',
    'this_month' => 'В этом месяце',
    'all_time' => 'За все время',
    
    // API и Webhook
    'webhook_url' => 'URL для webhook',
    'webhook_url_hint' => 'Укажите этот URL в настройках магазина FreeKassa',
    'test_api_connection' => 'Проверить соединение с API',
    'api_connection_success' => 'Соединение с API установлено успешно',
    'api_connection_failed' => 'Ошибка соединения с API',
    'get_balance' => 'Получить баланс',
    'balance' => 'Баланс',
    'shop_balance' => 'Баланс магазина',
    
    // Уведомления
    'notifications' => 'Уведомления',
    'telegram_notify' => 'Уведомления в Telegram',
    'email_notify' => 'Уведомления на Email',
    'notify_on_payment' => 'Уведомлять о платежах',
    
    // Безопасность
    'security' => 'Безопасность',
    'check_ip' => 'Проверять IP адрес',
    'check_ip_hint' => 'Проверка IP адреса отправителя webhook',
    'allowed_ips' => 'Разрешенные IP адреса',
    'allowed_ips_hint' => 'IP адреса серверов FreeKassa, разделенные запятой',
    
    // Сообщения об ошибках
    'error_occurred' => 'Произошла ошибка',
    'error_invalid_data' => 'Неверные данные',
    'error_instance_not_found' => 'Магазин не найден',
    'error_api_request_failed' => 'Ошибка запроса к API FreeKassa',
    'error_invalid_signature' => 'Неверная подпись',
    'error_invalid_amount' => 'Неверная сумма платежа',
    'error_missing_credentials' => 'Не указаны данные доступа к API',
    'error_payment_creation_failed' => 'Ошибка создания платежа',
    'error_webhook_processing_failed' => 'Ошибка обработки webhook',
    
    // Успешные сообщения
    'success' => 'Успешно',
    'success_saved' => 'Данные успешно сохранены',
    'success_deleted' => 'Данные успешно удалены',
    'success_updated' => 'Данные успешно обновлены',
    
    // Помощь и подсказки
    'help' => 'Помощь',
    'documentation' => 'Документация',
    'need_help' => 'Нужна помощь?',
    'visit_documentation' => 'Посетите документацию FreeKassa',
    'support' => 'Поддержка',
    
    // Дополнительные настройки
    'advanced_settings' => 'Дополнительные настройки',
    'success_url' => 'URL успешной оплаты',
    'failure_url' => 'URL неудачной оплаты',
    'notification_url' => 'URL уведомлений',
    'custom_urls' => 'Пользовательские URL',
    'custom_urls_hint' => 'Переопределение стандартных URL (требуется разрешение от поддержки FreeKassa)',
    
    // Логи
    'logs' => 'Логи',
    'api_logs' => 'Логи API',
    'view_logs' => 'Просмотр логов',
    'clear_logs' => 'Очистить логи',
    'logs_cleared' => 'Логи очищены',
    
    // Таблицы
    'no_data' => 'Нет данных',
    'loading' => 'Загрузка...',
    'total' => 'Всего',
    'page' => 'Страница',
    'of' => 'из',
    'show' => 'Показать',
    'entries' => 'записей',
    
    // Подтверждения
    'are_you_sure' => 'Вы уверены?',
    'confirm_action' => 'Подтвердите действие',
    'this_action_cannot_be_undone' => 'Это действие нельзя отменить',
    
    // Дополнительные опции
    'settings' => 'Настройки',
    'general_settings' => 'Общие настройки',
    'display_settings' => 'Настройки отображения',
    'show_in_menu' => 'Показывать в меню',
    'priority' => 'Приоритет',
    'order' => 'Порядок',
    'no_payment_methods' => 'Доступные методы оплаты не найдены',
    'no_instances' => 'Активные магазины не найдены',
    'freekassa_desc' => 'Платежная система FreeKassa с поддержкой множественных способов оплаты'
];
