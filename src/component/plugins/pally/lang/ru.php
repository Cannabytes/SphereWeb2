<?php

return [
    'pally_title' => 'Pally',
    'pally_payment_title' => 'Пополнение через Pally',
    'pally_payment_description' => 'Введите сумму и перейдите к оплате',
    'pally_amount_label' => 'Сумма',
    'pally_pay_button' => 'Перейти к оплате',
    'pally_min_max' => 'Минимум: %s, максимум: %s',
    'pally_minimum' => 'Минимум',
    'pally_maximum' => 'Максимум',

    'pally_description' => 'Платежная система Pally для оплаты через различные методы оплаты',
    
    // Admin panel — шлюзы
    'pally_gateways_title'            => 'Шлюзы',
    'pally_add_gateway'               => 'Добавить шлюз',
    'pally_gateway_label'             => 'Описание',
    'pally_gateway_label_placeholder' => 'напр. Visa/Mastercard',
    'pally_action_label'              => 'Действие',
    'pally_remove_gateway'            => 'Удалить',
    'pally_gateways_description'      => 'Каждый шлюз может иметь свой API Key, Shop ID, валюту и описание.',
    'pally_select_gateway'            => 'Выберите платёжный шлюз',

    // Admin panel
    'pally_admin_title' => 'Pally',
    'pally_admin_description' => 'Управление платежной системой Pally',
    'pally_activate_plugin' => 'Активировать plugin',
    'pally_plugin_disabled' => 'Plugin выключен. Включите toggle выше.',
    'pally_webhook_url' => 'Webhook URL',
    'pally_webhook_copy' => 'Копировать',
    'pally_webhook_note' => 'Скопируйте и укажите этот URL в кабинете Pally.',
    'pally_shop_id' => 'Shop ID',
    'pally_api_key' => 'API Key',
    'pally_currency' => 'Валюта',
    'pally_supported_countries' => 'Поддерживаемые страны',
    'pally_save_settings' => 'Сохранить настройки',
    
    // Messages
    'pally_fill_credentials' => 'Добавьте хотя бы один шлюз с shop_id и api_key',
    'pally_settings_saved' => 'Настройки Pally сохранены',
    'pally_not_configured' => 'Pally не настроен. Обратитесь к администратору.',
    'pally_enter_amount' => 'Введите сумму цифрой',
    'pally_not_configured_admin' => 'Pally не настроен',
    'pally_min_amount' => 'Минимальное пополнение: %s',
    'pally_max_amount' => 'Максимальное пополнение: %s',
    'pally_payment_creating' => 'Создание платежа...',
    'pally_curl_error' => 'Pally CURL error: %s',
    'pally_invalid_response' => 'Некорректный ответ Pally',
    'pally_api_error' => 'Pally error: %s',
    'pally_no_payment_link' => 'Pally не вернул ссылку на оплату',
    'pally_payment_error' => 'Неизвестная ошибка при создании платежа',
    'pally_connection_error' => 'Ошибка соединения',
    'pally_estimated_cost' => 'Примерная стоимость',
];
