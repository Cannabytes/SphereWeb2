<?php

return [
    // Plugin metadata
    'unitpay'                       => 'UnitPay',
    'unitpay_desc'                  => 'UnitPay payment system',
    'unitpay_gateway_description'   => 'UnitPay payment gateway',

    // Payment page
    'unitpay_payment_title'         => 'Top up via UnitPay',
    'unitpay_payment_description'   => 'Enter the amount and proceed to payment',
    'unitpay_amount_label'          => 'Amount',
    'unitpay_pay_button'            => 'Proceed to payment',
    'unitpay_minimum'               => 'Minimum',
    'unitpay_maximum'               => 'Maximum',
    'unitpay_currency_info'         => 'Payment currency',
    'unitpay_payment_creating'      => 'Creating payment...',

    // Admin panel
    'unitpay_admin_title'               => 'UnitPay',
    'unitpay_admin_description'         => 'UnitPay payment system settings',
    'unitpay_activate_plugin'           => 'Activate plugin',
    'unitpay_plugin_disabled'           => 'Plugin is disabled. Enable the toggle above.',
    'unitpay_webhook_url'               => 'Webhook URL',
    'unitpay_webhook_copy'              => 'Copy',
    'unitpay_webhook_note'              => 'Copy and specify this URL in your UnitPay project settings (Notifications).',
    'unitpay_credentials_title'         => 'API Credentials',
    'unitpay_public_key'                => 'Public Key (Project ID)',
    'unitpay_public_key_placeholder'    => 'e.g. demo-1234567890',
    'unitpay_public_key_hint'           => 'Public key from your UnitPay project settings.',
    'unitpay_secret_key'                => 'Secret Key',
    'unitpay_secret_key_placeholder'    => 'Enter secret key',
    'unitpay_secret_key_hint'           => 'Secret key from your UnitPay project settings.',
    'unitpay_currency'                  => 'Currency',
    'unitpay_currency_hint'             => 'The currency used for payment processing.',
    'unitpay_supported_countries'       => 'Supported countries',
    'unitpay_additional_settings'       => 'Additional settings',
    'unitpay_show_main_page'            => 'Show on main page',
    'unitpay_add_to_menu'               => 'Add to menu',
    'unitpay_shop'                      => 'Shop',
    'unitpay_shop_placeholder'          => 'e.g. 1,2,3',
    'unitpay_shop_hint'                 => 'Comma-separated list of server IDs where the plugin is available.',
    'unitpay_save_settings'             => 'Save settings',

    // Messages
    'unitpay_settings_saved'            => 'UnitPay settings saved',
    'unitpay_not_configured'            => 'UnitPay is not configured. Please contact the administrator.',
    'unitpay_enter_correct_amount'      => 'Please enter a valid amount.',
    'unitpay_min_amount'                => 'Minimum top-up amount: %s',
    'unitpay_max_amount'                => 'Maximum top-up amount: %s',
    'unitpay_api_error'                 => 'UnitPay API error. Please try again later.',
    'unitpay_connection_error'          => 'Connection error. Please try again.',
];
