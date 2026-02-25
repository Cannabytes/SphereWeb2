<?php

/**
 * Spanish translations for FreeKassa plugin
 */

return [
    // General
    'freekassa' => 'FreeKassa',
    'freekassa_payment_system' => 'Sistema de Pago FreeKassa',
    'freekassa_description' => 'Recepción de pagos a través de FreeKassa con soporte para múltiples tiendas y monedas',
    'plugin_enabled' => 'Habilitar complemento',
    'plugin_disabled' => 'Sistema de pago deshabilitado temporalmente',
    'settings_saved' => 'Configuración guardada con éxito',
    
    // Admin panel
    'admin_panel' => 'Panel de Control de FreeKassa',
    'plugin_settings' => 'Configuración del Complemento',
    'instances_management' => 'Gestión de Tiendas',
    'transaction_history' => 'Historial de Transacciones',
    'api_settings' => 'Configuración de API',
    'statistics' => 'Estadísticas',
    
    // Store management (Instances)
    'instances' => 'Tiendas',
    'add_instance' => 'Agregar Tienda',
    'edit_instance' => 'Editar Tienda',
    'delete_instance' => 'Eliminar Tienda',
    'instance_name' => 'Nombre de la Tienda',
    'instance_description' => 'Descripción de la Tienda',
    'no_instances' => 'No hay tiendas creadas',
    'instance_created' => 'Tienda creada exitosamente',
    'instance_updated' => 'Tienda actualizada exitosamente',
    'instance_deleted' => 'Tienda eliminada exitosamente',
    'confirm_delete_instance' => '¿Está seguro de que desea eliminar esta tienda?',
    
    // Store settings
    'shop_id' => 'ID de la Tienda',
    'shop_id_placeholder' => 'Ingrese el ID de la tienda FreeKassa',
    'shop_id_hint' => 'Su ID de tienda de la cuenta personal de FreeKassa',
    'api_key' => 'Clave de API',
    'api_key_placeholder' => 'Ingrese la clave de API',
    'api_key_hint' => 'Clave de API de la configuración de la tienda FreeKassa',
    'secret_word' => 'Palabra Secreta',
    'secret_word_placeholder' => 'Palabra secreta para firma de formulario',
    'secret_word_hint' => 'Palabra secreta de la configuración de la tienda (para formulario de pago)',
    'secret_word_2' => 'Palabra Secreta 2',
    'secret_word_2_placeholder' => 'Palabra secreta para verificación de webhook',
    'secret_word_2_hint' => 'Palabra secreta 2 de la configuración de la tienda (para notificaciones)',
    
    // Currencies and payment methods
    'currency' => 'Moneda',
    'currency_default' => 'Moneda Predeterminada',
    'payment_method' => 'Método de Pago',
    'payment_method_default' => 'Método de Pago Predeterminado',
    'select_currency' => 'Seleccionar Moneda',
    'select_payment_method' => 'Seleccionar Método de Pago',
    'available_currencies' => 'Monedas Disponibles',
    'available_payment_methods' => 'Métodos de Pago Disponibles',
    'refresh_currencies' => 'Actualizar Lista de Monedas',
    'currencies_updated' => 'Lista de monedas actualizada exitosamente',
    'limits' => 'Límites',
    'fee' => 'Comisión',
    
    // Currencies
    'RUB' => 'Rublo Ruso',
    'USD' => 'Dólar Estadounidense',
    'EUR' => 'Euro',
    'UAH' => 'Grivnia Ucraniana',
    'KZT' => 'Tenge Kazajo',
    
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
    'payment_method_12' => 'MIR',
    'payment_method_13' => 'Banca en Línea',
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
    'payment_method_28' => 'MegaFon',
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
    'payment_method_42' => 'SBP',
    'payment_method_44' => 'SBP (API)',
    
    // Statuses
    'enabled' => 'Habilitado',
    'disabled' => 'Deshabilitado',
    'status' => 'Estado',
    'test_mode' => 'Modo de Prueba',
    'test_mode_hint' => 'En modo de prueba, los pagos no se procesan',
    'active' => 'Activo',
    'inactive' => 'Inactivo',
    
    // User section
    'payment_title' => 'Recargar Saldo a través de FreeKassa',
    'payment_subtitle' => 'Seleccione método de pago e ingrese el monto',
    'select_shop' => 'Seleccionar Tienda',
    'amount' => 'Monto',
    'amount_placeholder' => 'Ingrese el monto de pago',
    'amount_hint' => 'Mínimo: {min}, Máximo: {max}',
    'min_amount' => 'Monto Mínimo',
    'max_amount' => 'Monto Máximo',
    'email' => 'Correo Electrónico',
    'email_placeholder' => 'Ingrese su correo electrónico',
    'phone' => 'Teléfono',
    'phone_placeholder' => 'Ingrese su teléfono (opcional)',
    'pay_button' => 'Ir al Pago',
    'creating_payment' => 'Creando pago...',
    'payment_created' => 'Pago creado! Redirigiendo a la página de pago...',
    
    // Transactions
    'transactions' => 'Transacciones',
    'transaction_id' => 'ID de Transacción',
    'order_id' => 'ID de Orden',
    'payment_id' => 'ID de Pago',
    'user' => 'Usuario',
    'date' => 'Fecha',
    'no_transactions' => 'Sin transacciones',
    
    // Transaction statuses
    'status_pending' => 'Esperando Pago',
    'status_completed' => 'Pagado',
    'status_failed' => 'Error',
    'status_refunded' => 'Reembolso',
    'status_cancelled' => 'Cancelado',
    
    // Actions
    'save' => 'Guardar',
    'cancel' => 'Cancelar',
    'delete' => 'Eliminar',
    'edit' => 'Editar',
    'view' => 'Ver',
    'close' => 'Cerrar',
    'back' => 'Atrás',
    'refresh' => 'Actualizar',
    'search' => 'Buscar',
    'filter' => 'Filtro',
    'export' => 'Exportar',
    'action' => 'Acción',
    'actions' => 'Acciones',
    
    // Statistics
    'total_transactions' => 'Total de Transacciones',
    'successful_payments' => 'Pagos Exitosos',
    'total_amount' => 'Monto Total',
    'today' => 'Hoy',
    'this_week' => 'Esta Semana',
    'this_month' => 'Este Mes',
    'all_time' => 'Todo el Tiempo',
    
    // API and Webhook
    'webhook_url' => 'URL de Webhook',
    'webhook_url_hint' => 'Especifique esta URL en la configuración de la tienda FreeKassa',
    'test_api_connection' => 'Probar Conexión de API',
    'api_connection_success' => 'Conexión de API establecida exitosamente',
    'api_connection_failed' => 'Error en la conexión de API',
    'get_balance' => 'Obtener Saldo',
    'balance' => 'Saldo',
    'shop_balance' => 'Saldo de la Tienda',
    
    // Notifications
    'notifications' => 'Notificaciones',
    'telegram_notify' => 'Notificaciones de Telegram',
    'email_notify' => 'Notificaciones por Correo Electrónico',
    'notify_on_payment' => 'Notificar en Pagos',
    
    // Security
    'security' => 'Seguridad',
    'check_ip' => 'Verificar Dirección IP',
    'check_ip_hint' => 'Verificar dirección IP del remitente del webhook',
    'allowed_ips' => 'Direcciones IP Permitidas',
    'allowed_ips_hint' => 'Direcciones IP del servidor FreeKassa, separadas por comas',
    
    // Error messages
    'error_occurred' => 'Ocurrió un error',
    'error_invalid_data' => 'Datos inválidos',
    'error_instance_not_found' => 'Tienda no encontrada',
    'error_api_request_failed' => 'Error en la solicitud de API de FreeKassa',
    'error_invalid_signature' => 'Firma inválida',
    'error_invalid_amount' => 'Monto de pago inválido',
    'error_missing_credentials' => 'Credenciales de API no especificadas',
    'error_payment_creation_failed' => 'Error al crear el pago',
    'error_webhook_processing_failed' => 'Error en el procesamiento del webhook',
    
    // Success messages
    'success' => 'Éxito',
    'success_saved' => 'Datos guardados exitosamente',
    'success_deleted' => 'Datos eliminados exitosamente',
    'success_updated' => 'Datos actualizados exitosamente',
    
    // Help and hints
    'help' => 'Ayuda',
    'documentation' => 'Documentación',
    'need_help' => '¿Necesita ayuda?',
    'visit_documentation' => 'Visite la documentación de FreeKassa',
    'support' => 'Soporte',
    
    // Advanced settings
    'advanced_settings' => 'Configuración Avanzada',
    'success_url' => 'URL de Pago Exitoso',
    'failure_url' => 'URL de Pago Fallido',
    'notification_url' => 'URL de Notificación',
    'custom_urls' => 'URLs Personalizadas',
    'custom_urls_hint' => 'Anular URLs predeterminadas (requiere permiso de soporte de FreeKassa)',
    
    // Logs
    'logs' => 'Registros',
    'api_logs' => 'Registros de API',
    'view_logs' => 'Ver Registros',
    'clear_logs' => 'Limpiar Registros',
    'logs_cleared' => 'Registros limpiados',
    
    // Tables
    'no_data' => 'Sin datos',
    'loading' => 'Cargando...',
    'total' => 'Total',
    'page' => 'Página',
    'of' => 'de',
    'show' => 'Mostrar',
    'entries' => 'entradas',
    
    // Confirmations
    'are_you_sure' => '¿Está seguro?',
    'confirm_action' => 'Confirmar acción',
    'this_action_cannot_be_undone' => 'Esta acción no se puede deshacer',
    
    // Additional options
    'settings' => 'Configuración',
    'general_settings' => 'Configuración General',
    'display_settings' => 'Configuración de Pantalla',
    'show_in_menu' => 'Mostrar en Menú',
    'priority' => 'Prioridad',
    'order' => 'Orden',
    'no_payment_methods' => 'Métodos de pago disponibles no encontrados',
    'no_instances' => 'Tiendas activas no encontradas',
    'freekassa_desc' => 'Sistema de pago FreeKassa con soporte para múltiples métodos de pago'
];
