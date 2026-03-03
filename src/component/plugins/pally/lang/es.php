<?php

return [
    'pally_title' => 'Pally',
    'pally_description' => 'Sistema de pago Pally para pagos a través de diversos métodos de pago',
    'pally_payment_title' => 'Recarga a través de Pally',
    'pally_payment_description' => 'Ingrese la cantidad y proceda al pago',
    'pally_amount_label' => 'Cantidad',
    'pally_pay_button' => 'Ir al pago',
    'pally_min_max' => 'Mínimo: %s, Máximo: %s',
    'pally_minimum' => 'Mínimo',
    'pally_maximum' => 'Máximo',
    
    // Admin panel — pasarelas
    'pally_gateways_title'            => 'Pasarelas de pago',
    'pally_add_gateway'               => 'Agregar pasarela',
    'pally_gateway_label'             => 'Descripción',
    'pally_gateway_label_placeholder' => 'ej. Visa/Mastercard',
    'pally_action_label'              => 'Acción',
    'pally_remove_gateway'            => 'Eliminar',
    'pally_gateways_description'      => 'Cada pasarela puede tener su propia clave API, ID de tienda, moneda y descripción.',
    'pally_select_gateway'            => 'Seleccione la pasarela de pago',

    // Admin panel
    'pally_admin_title' => 'Pally',
    'pally_admin_description' => 'Gestión del sistema de pago Pally',
    'pally_activate_plugin' => 'Activar complemento',
    'pally_plugin_disabled' => 'El complemento está deshabilitado. Habilite el botón de arriba.',
    'pally_webhook_url' => 'URL de Webhook',
    'pally_webhook_copy' => 'Copiar',
    'pally_webhook_note' => 'Copie y especifique esta URL en su cuenta de Pally.',
    'pally_shop_id' => 'ID de Tienda',
    'pally_api_key' => 'Clave API',
    'pally_currency' => 'Moneda',
    'pally_supported_countries' => 'Países soportados',
    'pally_save_settings' => 'Guardar configuración',
    
    // Messages
    'pally_fill_credentials' => 'Agregue al menos una pasarela con shop_id y api_key',
    'pally_settings_saved' => 'Configuración de Pally guardada',
    'pally_not_configured' => 'Pally no está configurado. Póngase en contacto con el administrador.',
    'pally_enter_amount' => 'Ingrese la cantidad como número',
    'pally_not_configured_admin' => 'Pally no está configurado',
    'pally_min_amount' => 'Recarga mínima: %s',
    'pally_max_amount' => 'Recarga máxima: %s',
    'pally_payment_creating' => 'Creando pago...',
    'pally_curl_error' => 'Error CURL de Pally: %s',
    'pally_invalid_response' => 'Respuesta inválida de Pally',
    'pally_api_error' => 'Error de Pally: %s',
    'pally_no_payment_link' => 'Pally no devolvió un enlace de pago',
    'pally_payment_error' => 'Error desconocido al crear el pago',
    'pally_connection_error' => 'Error de conexión',
    'pally_estimated_cost' => 'Costo estimado',
];
