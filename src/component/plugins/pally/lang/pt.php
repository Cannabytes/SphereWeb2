<?php

return [
    'pally_title' => 'Pally',
    'pally_description' => 'Sistema de pagamento Pally para pagamento através de vários métodos de pagamento',
    'pally_payment_title' => 'Recarga via Pally',
    'pally_payment_description' => 'Digite o valor e prossiga para o pagamento',
    'pally_amount_label' => 'Valor',
    'pally_pay_button' => 'Ir para o pagamento',
    'pally_min_max' => 'Mínimo: %s, Máximo: %s',
    'pally_minimum' => 'Mínimo',
    'pally_maximum' => 'Máximo',
    
    // Admin panel — gateways
    'pally_gateways_title'            => 'Gateways de pagamento',
    'pally_add_gateway'               => 'Adicionar gateway',
    'pally_gateway_label'             => 'Descrição',
    'pally_gateway_label_placeholder' => 'ex. Visa/Mastercard',
    'pally_action_label'              => 'Ação',
    'pally_remove_gateway'            => 'Remover',
    'pally_gateways_description'      => 'Cada gateway pode ter sua própria Chave API, ID da Loja, moeda e descrição.',
    'pally_select_gateway'            => 'Selecione o gateway de pagamento',

    // Admin panel
    'pally_admin_title' => 'Pally',
    'pally_admin_description' => 'Gerenciamento do sistema de pagamento Pally',
    'pally_activate_plugin' => 'Ativar plugin',
    'pally_plugin_disabled' => 'Plugin desativado. Ative o botão acima.',
    'pally_webhook_url' => 'URL do Webhook',
    'pally_webhook_copy' => 'Copiar',
    'pally_webhook_note' => 'Copie e especifique esta URL na sua conta Pally.',
    'pally_shop_id' => 'ID da Loja',
    'pally_api_key' => 'Chave API',
    'pally_currency' => 'Moeda',
    'pally_supported_countries' => 'Países suportados',
    'pally_save_settings' => 'Salvar configurações',
    
    // Messages
    'pally_fill_credentials' => 'Adicione pelo menos um gateway com shop_id e api_key',
    'pally_settings_saved' => 'Configurações do Pally salvas',
    'pally_not_configured' => 'Pally não está configurado. Entre em contato com o administrador.',
    'pally_enter_amount' => 'Digite o valor como número',
    'pally_not_configured_admin' => 'Pally não está configurado',
    'pally_min_amount' => 'Recarga mínima: %s',
    'pally_max_amount' => 'Recarga máxima: %s',
    'pally_payment_creating' => 'Criando pagamento...',
    'pally_curl_error' => 'Erro CURL do Pally: %s',
    'pally_invalid_response' => 'Resposta inválida do Pally',
    'pally_api_error' => 'Erro do Pally: %s',
    'pally_no_payment_link' => 'Pally não retornou um link de pagamento',
    'pally_payment_error' => 'Erro desconhecido ao criar pagamento',
    'pally_connection_error' => 'Erro de conexão',
    'pally_estimated_cost' => 'Custo estimado',
];
