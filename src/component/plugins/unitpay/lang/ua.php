<?php

return [
    // Plugin metadata
    'unitpay'                       => 'UnitPay',
    'unitpay_desc'                  => 'Платіжна система UnitPay',
    'unitpay_gateway_description'   => 'Платіжний шлюз UnitPay',

    // Payment page
    'unitpay_payment_title'         => 'Поповнення через UnitPay',
    'unitpay_payment_description'   => 'Введіть суму та перейдіть до оплати',
    'unitpay_amount_label'          => 'Сума',
    'unitpay_pay_button'            => 'Перейти до оплати',
    'unitpay_minimum'               => 'Мінімум',
    'unitpay_maximum'               => 'Максимум',
    'unitpay_currency_info'         => 'Валюта оплати',
    'unitpay_payment_creating'      => 'Створення платежу...',

    // Admin panel
    'unitpay_admin_title'               => 'UnitPay',
    'unitpay_admin_description'         => 'Налаштування платіжної системи UnitPay',
    'unitpay_activate_plugin'           => 'Активувати плагін',
    'unitpay_plugin_disabled'           => 'Плагін вимкнено. Увімкніть перемикач вище.',
    'unitpay_webhook_url'               => 'Webhook URL',
    'unitpay_webhook_copy'              => 'Копіювати',
    'unitpay_webhook_note'              => 'Скопіюйте та вкажіть цей URL у налаштуваннях проекту UnitPay (Сповіщення).',
    'unitpay_credentials_title'         => 'Облікові дані API',
    'unitpay_public_key'                => 'Публічний ключ (ID проекту)',
    'unitpay_public_key_placeholder'    => 'наприклад, demo-1234567890',
    'unitpay_public_key_hint'           => 'Публічний ключ з налаштувань проекту UnitPay.',
    'unitpay_secret_key'                => 'Секретний ключ',
    'unitpay_secret_key_placeholder'    => 'Введіть секретний ключ',
    'unitpay_secret_key_hint'           => 'Секретний ключ з налаштувань проекту UnitPay.',
    'unitpay_currency'                  => 'Валюта',
    'unitpay_currency_hint'             => 'Валюта, що використовується для обробки платежів.',
    'unitpay_supported_countries'       => 'Підтримувані країни',
    'unitpay_additional_settings'       => 'Додаткові налаштування',
    'unitpay_show_main_page'            => 'Показувати на головній сторінці',
    'unitpay_add_to_menu'               => 'Додати до меню',
    'unitpay_shop'                      => 'Магазин',
    'unitpay_shop_placeholder'          => 'наприклад, 1,2,3',
    'unitpay_shop_hint'                 => 'Список ID серверів через кому, де доступний плагін.',
    'unitpay_save_settings'             => 'Зберегти налаштування',

    // Messages
    'unitpay_settings_saved'            => 'Налаштування UnitPay збережено',
    'unitpay_not_configured'            => 'UnitPay не налаштовано. Зверніться до адміністратора.',
    'unitpay_enter_correct_amount'      => 'Введіть коректну суму.',
    'unitpay_min_amount'                => 'Мінімальна сума поповнення: %s',
    'unitpay_max_amount'                => 'Максимальна сума поповнення: %s',
    'unitpay_api_error'                 => 'Помилка API UnitPay. Спробуйте пізніше.',
    'unitpay_connection_error'          => 'Помилка з\'єднання. Спробуйте ще раз.',
    'unitpay_estimated_cost'            => 'Приблизна вартість',
];
