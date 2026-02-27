<?php

return [
    // Plugin metadata
    'unitpay'                       => 'UnitPay',
    'unitpay_desc'                  => 'Sistema de pago UnitPay',
    'unitpay_gateway_description'   => 'Pasarela de pago UnitPay',

    // Payment page
    'unitpay_payment_title'         => 'Recargar vía UnitPay',
    'unitpay_payment_description'   => 'Introduce el importe y procede al pago',
    'unitpay_amount_label'          => 'Importe',
    'unitpay_pay_button'            => 'Ir al pago',
    'unitpay_minimum'               => 'Mínimo',
    'unitpay_maximum'               => 'Máximo',
    'unitpay_currency_info'         => 'Moneda de pago',
    'unitpay_payment_creating'      => 'Creando pago...',

    // Admin panel
    'unitpay_admin_title'               => 'UnitPay',
    'unitpay_admin_description'         => 'Configuración del sistema de pago UnitPay',
    'unitpay_activate_plugin'           => 'Activar plugin',
    'unitpay_plugin_disabled'           => 'El plugin está desactivado. Activa el interruptor de arriba.',
    'unitpay_webhook_url'               => 'URL de Webhook',
    'unitpay_webhook_copy'              => 'Copiar',
    'unitpay_webhook_note'              => 'Copia y especifica esta URL en la configuración de tu proyecto UnitPay (Notificaciones).',
    'unitpay_credentials_title'         => 'Credenciales de API',
    'unitpay_public_key'                => 'Clave pública (ID de proyecto)',
    'unitpay_public_key_placeholder'    => 'p.ej. demo-1234567890',
    'unitpay_public_key_hint'           => 'Clave pública de la configuración de tu proyecto UnitPay.',
    'unitpay_secret_key'                => 'Clave secreta',
    'unitpay_secret_key_placeholder'    => 'Introduce la clave secreta',
    'unitpay_secret_key_hint'           => 'Clave secreta de la configuración de tu proyecto UnitPay.',
    'unitpay_currency'                  => 'Moneda',
    'unitpay_currency_hint'             => 'Moneda utilizada para el procesamiento de pagos.',
    'unitpay_supported_countries'       => 'Países admitidos',
    'unitpay_save_settings'             => 'Guardar configuración',

    // Messages
    'unitpay_settings_saved'            => 'Configuración de UnitPay guardada',
    'unitpay_not_configured'            => 'UnitPay no está configurado. Contacta al administrador.',
    'unitpay_enter_correct_amount'      => 'Introduce un importe válido.',
    'unitpay_min_amount'                => 'Importe mínimo de recarga: %s',
    'unitpay_max_amount'                => 'Importe máximo de recarga: %s',
    'unitpay_api_error'                 => 'Error de API de UnitPay. Inténtalo más tarde.',
    'unitpay_connection_error'          => 'Error de conexión. Inténtalo de nuevo.',
];
