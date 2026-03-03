<?php

return [
    // Plugin metadata
    'unitpay'                       => 'UnitPay',
    'unitpay_desc'                  => 'Платёжная система UnitPay',
    'unitpay_gateway_description'   => 'Платёжный шлюз UnitPay',

    // Payment page
    'unitpay_payment_title'         => 'Пополнение через UnitPay',
    'unitpay_payment_description'   => 'Введите сумму и перейдите к оплате',
    'unitpay_amount_label'          => 'Сумма',
    'unitpay_pay_button'            => 'Перейти к оплате',
    'unitpay_minimum'               => 'Минимум',
    'unitpay_maximum'               => 'Максимум',
    'unitpay_currency_info'         => 'Валюта оплаты',
    'unitpay_payment_creating'      => 'Создание платежа...',

    // Admin panel
    'unitpay_admin_title'               => 'UnitPay',
    'unitpay_admin_description'         => 'Настройки платёжной системы UnitPay',
    'unitpay_activate_plugin'           => 'Активировать плагин',
    'unitpay_plugin_disabled'           => 'Плагин отключён. Включите переключатель выше.',
    'unitpay_webhook_url'               => 'Webhook URL',
    'unitpay_webhook_copy'              => 'Копировать',
    'unitpay_webhook_note'              => 'Скопируйте и укажите этот URL в настройках проекта UnitPay (Уведомления).',
    'unitpay_credentials_title'         => 'API Учётные данные',
    'unitpay_public_key'                => 'Публичный ключ (ID проекта)',
    'unitpay_public_key_placeholder'    => 'например, demo-1234567890',
    'unitpay_public_key_hint'           => 'Публичный ключ из настроек проекта UnitPay.',
    'unitpay_secret_key'                => 'Секретный ключ',
    'unitpay_secret_key_placeholder'    => 'Введите секретный ключ',
    'unitpay_secret_key_hint'           => 'Секретный ключ из настроек проекта UnitPay.',
    'unitpay_currency'                  => 'Валюта',
    'unitpay_currency_hint'             => 'Валюта, используемая при обработке платежей.',
    'unitpay_supported_countries'       => 'Поддерживаемые страны',
    'unitpay_additional_settings'       => 'Дополнительные настройки',
    'unitpay_show_main_page'            => 'Показывать на главной странице',
    'unitpay_add_to_menu'               => 'Добавить в меню',
    'unitpay_shop'                      => 'Магазин',
    'unitpay_shop_placeholder'          => 'например, 1,2,3',
    'unitpay_shop_hint'                 => 'Список ID серверов через запятую, где доступен плагин.',
    'unitpay_save_settings'             => 'Сохранить настройки',

    // Messages
    'unitpay_settings_saved'            => 'Настройки UnitPay сохранены',
    'unitpay_not_configured'            => 'UnitPay не настроен. Обратитесь к администратору.',
    'unitpay_enter_correct_amount'      => 'Введите корректную сумму.',
    'unitpay_min_amount'                => 'Минимальная сумма пополнения: %s',
    'unitpay_max_amount'                => 'Максимальная сумма пополнения: %s',
    'unitpay_api_error'                 => 'Ошибка API UnitPay. Попробуйте позже.',
    'unitpay_connection_error'          => 'Ошибка соединения. Попробуйте ещё раз.',
    'unitpay_estimated_cost'            => 'Примерная стоимость',
];
