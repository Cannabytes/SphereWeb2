<?php

/**
 * Ukrainian translations for FreeKassa plugin
 */

return [
    // General
    'freekassa' => 'FreeKassa',
    'freekassa_payment_system' => 'Платіжна система FreeKassa',
    'freekassa_description' => 'Прийом платежів через FreeKassa з підтримкою кількох магазинів і валют',
    'plugin_enabled' => 'Активувати плагін',
    'plugin_disabled' => 'Платіжна система тимчасово вимкнена',
    'settings_saved' => 'Налаштування успішно збережені',
    
    // Admin panel
    'admin_panel' => 'Панель управління FreeKassa',
    'plugin_settings' => 'Налаштування плагіну',
    'instances_management' => 'Управління магазинами',
    'transaction_history' => 'Історія транзакцій',
    'api_settings' => 'Налаштування API',
    'statistics' => 'Статистика',
    
    // Store management (Instances)
    'instances' => 'Магазини',
    'add_instance' => 'Додати магазин',
    'edit_instance' => 'Редагувати магазин',
    'delete_instance' => 'Видалити магазин',
    'instance_name' => 'Назва магазину',
    'instance_description' => 'Опис магазину',
    'no_instances' => 'Магазини не створені',
    'instance_created' => 'Магазин успішно створений',
    'instance_updated' => 'Магазин успішно оновлений',
    'instance_deleted' => 'Магазин успішно видалений',
    'confirm_delete_instance' => 'Ви впевнені, що хочете видалити цей магазин?',
    
    // Store settings
    'shop_id' => 'ID магазину',
    'shop_id_placeholder' => 'Введіть ID магазину FreeKassa',
    'shop_id_hint' => 'ID вашого магазину з особистого кабінету FreeKassa',
    'api_key' => 'API ключ',
    'api_key_placeholder' => 'Введіть API ключ',
    'api_key_hint' => 'API ключ з налаштувань магазину FreeKassa',
    'secret_word' => 'Секретне слово',
    'secret_word_placeholder' => 'Секретне слово для підпису форми',
    'secret_word_hint' => 'Секретне слово з налаштувань магазину (для форми оплати)',
    'secret_word_2' => 'Секретне слово 2',
    'secret_word_2_placeholder' => 'Секретне слово для перевірки webhook',
    'secret_word_2_hint' => 'Секретне слово 2 з налаштувань магазину (для повідомлень)',
    
    // Currencies and payment methods
    'currency' => 'Валюта',
    'currency_default' => 'Валюта за замовчуванням',
    'payment_method' => 'Метод платежу',
    'payment_method_default' => 'Метод платежу за замовчуванням',
    'select_currency' => 'Виберіть валюту',
    'select_payment_method' => 'Виберіть спосіб оплати',
    'available_currencies' => 'Доступні валюти',
    'available_payment_methods' => 'Доступні способи оплати',
    'refresh_currencies' => 'Оновити список валют',
    'currencies_updated' => 'Список валют успішно оновлений',
    'limits' => 'Ліміти',
    'fee' => 'Комісія',
    
    // Currencies
    'RUB' => 'Російський рубль',
    'USD' => 'Американський долар',
    'EUR' => 'Євро',
    'UAH' => 'Українська гривня',
    'KZT' => 'Казахський тенге',
    
    // Payment methods
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
    'payment_method_12' => 'МІР',
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
    'payment_method_37' => 'Google Pay',
    'payment_method_38' => 'Apple Pay',
    'payment_method_39' => 'Tron',
    'payment_method_40' => 'Webmoney WMZ',
    'payment_method_41' => 'VISA / MasterCard KZT',
    'payment_method_42' => 'СБП',
    'payment_method_44' => 'СБП (API)',
    
    // Statuses
    'enabled' => 'Увімкнено',
    'disabled' => 'Вимкнено',
    'status' => 'Статус',
    'test_mode' => 'Тестовий режим',
    'test_mode_hint' => 'У тестовому режимі платежі не обробляються',
    'active' => 'Активно',
    'inactive' => 'Неактивно',
    
    // User section
    'payment_title' => 'Поповнення балансу через FreeKassa',
    'payment_subtitle' => 'Виберіть спосіб оплати та введіть суму',
    'select_shop' => 'Виберіть магазин',
    'amount' => 'Сума',
    'amount_placeholder' => 'Введіть суму платежу',
    'amount_hint' => 'Мінімум: {min}, Максимум: {max}',
    'min_amount' => 'Мінімальна сума',
    'max_amount' => 'Максимальна сума',
    'email' => 'Email',
    'email_placeholder' => 'Введіть ваш email',
    'phone' => 'Телефон',
    'phone_placeholder' => 'Введіть ваш телефон (необов\'язково)',
    'pay_button' => 'Перейти до оплати',
    'creating_payment' => 'Створення платежу...',
    'payment_created' => 'Платіж створено! Перенаправлення на сторінку оплати...',
    
    // Transactions
    'transactions' => 'Транзакції',
    'transaction_id' => 'ID транзакції',
    'order_id' => 'ID замовлення',
    'payment_id' => 'ID платежу',
    'user' => 'Користувач',
    'date' => 'Дата',
    'no_transactions' => 'Транзакції відсутні',
    
    // Transaction statuses
    'status_pending' => 'Очікування платежу',
    'status_completed' => 'Оплачено',
    'status_failed' => 'Помилка',
    'status_refunded' => 'Повернення',
    'status_cancelled' => 'Скасовано',
    
    // Actions
    'save' => 'Зберегти',
    'cancel' => 'Скасувати',
    'delete' => 'Видалити',
    'edit' => 'Редагувати',
    'view' => 'Переглянути',
    'close' => 'Закрити',
    'back' => 'Назад',
    'refresh' => 'Оновити',
    'search' => 'Пошук',
    'filter' => 'Фільтр',
    'export' => 'Експорт',
    'action' => 'Дія',
    'actions' => 'Дії',
    
    // Statistics
    'total_transactions' => 'Всього транзакцій',
    'successful_payments' => 'Успішних платежів',
    'total_amount' => 'Загальна сума',
    'today' => 'Сьогодні',
    'this_week' => 'На цьому тижні',
    'this_month' => 'У цьому місяці',
    'all_time' => 'За весь час',
    
    // API and Webhook
    'webhook_url' => 'URL Webhook',
    'webhook_url_hint' => 'Вкажіть цей URL у налаштуваннях магазину FreeKassa',
    'test_api_connection' => 'Перевірити з\'єднання з API',
    'api_connection_success' => 'З\'єднання з API встановлено успішно',
    'api_connection_failed' => 'Помилка з\'єднання з API',
    'get_balance' => 'Отримати баланс',
    'balance' => 'Баланс',
    'shop_balance' => 'Баланс магазину',
    
    // Notifications
    'notifications' => 'Сповіщення',
    'telegram_notify' => 'Сповіщення Telegram',
    'email_notify' => 'Email сповіщення',
    'notify_on_payment' => 'Сповіщувати про платежи',
    
    // Security
    'security' => 'Безпека',
    'check_ip' => 'Перевірити IP адресу',
    'check_ip_hint' => 'Перевіряти IP адресу відправника webhook',
    'allowed_ips' => 'Дозволені IP адреси',
    'allowed_ips_hint' => 'IP адреси серверів FreeKassa, розділені комами',
    
    // Error messages
    'error_occurred' => 'Сталася помилка',
    'error_invalid_data' => 'Невірні дані',
    'error_instance_not_found' => 'Магазин не знайдений',
    'error_api_request_failed' => 'Помилка запиту до API FreeKassa',
    'error_invalid_signature' => 'Невірна підпис',
    'error_invalid_amount' => 'Невірна сума платежу',
    'error_missing_credentials' => 'Не вказані дані доступу до API',
    'error_payment_creation_failed' => 'Помилка創ення платежу',
    'error_webhook_processing_failed' => 'Помилка обробки webhook',
    
    // Success messages
    'success' => 'Успішно',
    'success_saved' => 'Дані успішно збережені',
    'success_deleted' => 'Дані успішно видалені',
    'success_updated' => 'Дані успішно оновлені',
    
    // Help and hints
    'help' => 'Допомога',
    'documentation' => 'Документація',
    'need_help' => 'Потрібна допомога?',
    'visit_documentation' => 'Відвідайте документацію FreeKassa',
    'support' => 'Підтримка',
    
    // Advanced settings
    'advanced_settings' => 'Розширені налаштування',
    'success_url' => 'URL успішної оплати',
    'failure_url' => 'URL невдалої оплати',
    'notification_url' => 'URL сповіщень',
    'custom_urls' => 'Користувацькі URL',
    'custom_urls_hint' => 'Заміна стандартних URL (потребує дозволу від служби підтримки FreeKassa)',
    
    // Logs
    'logs' => 'Логи',
    'api_logs' => 'Логи API',
    'view_logs' => 'Переглянути логи',
    'clear_logs' => 'Очистити логи',
    'logs_cleared' => 'Логи очищені',
    
    // Tables
    'no_data' => 'Немає даних',
    'loading' => 'Завантаження...',
    'total' => 'Всього',
    'page' => 'Сторінка',
    'of' => 'з',
    'show' => 'Показати',
    'entries' => 'записів',
    
    // Confirmations
    'are_you_sure' => 'Ви впевнені?',
    'confirm_action' => 'Підтвердіть дію',
    'this_action_cannot_be_undone' => 'Цю дію неможливо скасувати',
    
    // Additional options
    'settings' => 'Налаштування',
    'general_settings' => 'Загальні налаштування',
    'display_settings' => 'Налаштування відображення',
    'show_in_menu' => 'Показати в меню',
    'priority' => 'Пріоритет',
    'order' => 'Порядок',
    'no_payment_methods' => 'Доступні методи оплати не знайдені',
    'no_instances' => 'Активні магазини не знайдені',
    'freekassa_desc' => 'Платіжна система FreeKassa з підтримкою кількох способів оплати'
];
