<?php

return [
    // Plugin metadata
    'unitpay'                       => 'UnitPay',
    'unitpay_desc'                  => 'Sistema de pagamento UnitPay',
    'unitpay_gateway_description'   => 'Gateway de pagamento UnitPay',

    // Payment page
    'unitpay_payment_title'         => 'Recarregar via UnitPay',
    'unitpay_payment_description'   => 'Informe o valor e prossiga para o pagamento',
    'unitpay_amount_label'          => 'Valor',
    'unitpay_pay_button'            => 'Ir para pagamento',
    'unitpay_minimum'               => 'Mínimo',
    'unitpay_maximum'               => 'Máximo',
    'unitpay_currency_info'         => 'Moeda de pagamento',
    'unitpay_payment_creating'      => 'Criando pagamento...',

    // Admin panel
    'unitpay_admin_title'               => 'UnitPay',
    'unitpay_admin_description'         => 'Configurações do sistema de pagamento UnitPay',
    'unitpay_activate_plugin'           => 'Ativar plugin',
    'unitpay_plugin_disabled'           => 'O plugin está desativado. Ative o botão acima.',
    'unitpay_webhook_url'               => 'URL do Webhook',
    'unitpay_webhook_copy'              => 'Copiar',
    'unitpay_webhook_note'              => 'Copie e especifique esta URL nas configurações do projeto UnitPay (Notificações).',
    'unitpay_credentials_title'         => 'Credenciais de API',
    'unitpay_public_key'                => 'Chave pública (ID do projeto)',
    'unitpay_public_key_placeholder'    => 'ex. demo-1234567890',
    'unitpay_public_key_hint'           => 'Chave pública nas configurações do projeto UnitPay.',
    'unitpay_secret_key'                => 'Chave secreta',
    'unitpay_secret_key_placeholder'    => 'Insira a chave secreta',
    'unitpay_secret_key_hint'           => 'Chave secreta nas configurações do projeto UnitPay.',
    'unitpay_currency'                  => 'Moeda',
    'unitpay_currency_hint'             => 'Moeda utilizada para processar pagamentos.',
    'unitpay_supported_countries'       => 'Países suportados',
    'unitpay_additional_settings'       => 'Configurações adicionais',
    'unitpay_show_main_page'            => 'Mostrar na página principal',
    'unitpay_add_to_menu'               => 'Adicionar ao menu',
    'unitpay_shop'                      => 'Loja',
    'unitpay_shop_placeholder'          => 'ex. 1,2,3',
    'unitpay_shop_hint'                 => 'Lista de IDs de servidores separados por vírgula onde o plugin está disponível.',
    'unitpay_save_settings'             => 'Salvar configurações',

    // Messages
    'unitpay_settings_saved'            => 'Configurações do UnitPay salvas',
    'unitpay_not_configured'            => 'UnitPay não está configurado. Entre em contato com o administrador.',
    'unitpay_enter_correct_amount'      => 'Insira um valor válido.',
    'unitpay_min_amount'                => 'Valor mínimo de recarga: %s',
    'unitpay_max_amount'                => 'Valor máximo de recarga: %s',
    'unitpay_api_error'                 => 'Erro na API do UnitPay. Tente novamente mais tarde.',
    'unitpay_connection_error'          => 'Erro de conexão. Tente novamente.',
];
