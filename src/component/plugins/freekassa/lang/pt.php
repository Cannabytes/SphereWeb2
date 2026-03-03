<?php

/**
 * Portuguese translations for FreeKassa plugin
 */

return [
    // General
    'freekassa' => 'FreeKassa',
    'freekassa_payment_system' => 'Sistema de Pagamento FreeKassa',
    'freekassa_description' => 'Recebimento de pagamentos através de FreeKassa com suporte para múltiplas lojas e moedas',
    'plugin_enabled' => 'Ativar plugin',
    'plugin_disabled' => 'Sistema de pagamento desativado temporariamente',
    'settings_saved' => 'Configurações salvas com sucesso',
    
    // Admin panel
    'admin_panel' => 'Painel de Controle do FreeKassa',
    'plugin_settings' => 'Configurações do Plugin',
    'instances_management' => 'Gerenciamento de Lojas',
    'transaction_history' => 'Histórico de Transações',
    'api_settings' => 'Configurações de API',
    'statistics' => 'Estatísticas',
    
    // Store management (Instances)
    'instances' => 'Lojas',
    'add_instance' => 'Adicionar Loja',
    'edit_instance' => 'Editar Loja',
    'delete_instance' => 'Excluir Loja',
    'instance_name' => 'Nome da Loja',
    'instance_description' => 'Descrição da Loja',
    'no_instances' => 'Nenhuma loja foi criada',
    'instance_created' => 'Loja criada com sucesso',
    'instance_updated' => 'Loja atualizada com sucesso',
    'instance_deleted' => 'Loja excluída com sucesso',
    'confirm_delete_instance' => 'Tem certeza de que deseja excluir esta loja?',
    
    // Store settings
    'shop_id' => 'ID da Loja',
    'shop_id_placeholder' => 'Insira o ID da loja FreeKassa',
    'shop_id_hint' => 'Seu ID de loja da conta pessoal do FreeKassa',
    'api_key' => 'Chave de API',
    'api_key_placeholder' => 'Insira a chave de API',
    'api_key_hint' => 'Chave de API das configurações da loja FreeKassa',
    'secret_word' => 'Palavra Secreta',
    'secret_word_placeholder' => 'Palavra secreta para assinatura do formulário',
    'secret_word_hint' => 'Palavra secreta das configurações da loja (para formulário de pagamento)',
    'secret_word_2' => 'Palavra Secreta 2',
    'secret_word_2_placeholder' => 'Palavra secreta para verificação de webhook',
    'secret_word_2_hint' => 'Palavra secreta 2 das configurações da loja (para notificações)',
    
    // Currencies and payment methods
    'currency' => 'Moeda',
    'currency_default' => 'Moeda Padrão',
    'payment_method' => 'Método de Pagamento',
    'payment_method_default' => 'Método de Pagamento Padrão',
    'select_currency' => 'Selecionar Moeda',
    'select_payment_method' => 'Selecionar Método de Pagamento',
    'available_currencies' => 'Moedas Disponíveis',
    'available_payment_methods' => 'Métodos de Pagamento Disponíveis',
    'refresh_currencies' => 'Atualizar Lista de Moedas',
    'currencies_updated' => 'Lista de moedas atualizada com sucesso',
    'limits' => 'Limites',
    'fee' => 'Taxa',
    
    // Currencies
    'RUB' => 'Rublo Russo',
    'USD' => 'Dólar Americano',
    'EUR' => 'Euro',
    'UAH' => 'Hryvnia Ucraniana',
    'KZT' => 'Tenge do Cazaquistão',
    
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
    'payment_method_13' => 'Banco Online',
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
    'enabled' => 'Ativado',
    'disabled' => 'Desativado',
    'status' => 'Status',
    'test_mode' => 'Modo de Teste',
    'test_mode_hint' => 'No modo de teste, os pagamentos não são processados',
    'active' => 'Ativo',
    'inactive' => 'Inativo',
    
    // User section
    'payment_title' => 'Recarregar Saldo via FreeKassa',
    'payment_subtitle' => 'Selecione método de pagamento e insira o valor',
    'select_shop' => 'Selecionar Loja',
    'amount' => 'Valor',
    'amount_placeholder' => 'Insira o valor do pagamento',
    'amount_hint' => 'Mínimo: {min}, Máximo: {max}',
    'min_amount' => 'Valor Mínimo',
    'max_amount' => 'Valor Máximo',
    'email' => 'Email',
    'email_placeholder' => 'Insira seu email',
    'phone' => 'Telefone',
    'phone_placeholder' => 'Insira seu telefone (opcional)',
    'pay_button' => 'Ir para o Pagamento',
    'creating_payment' => 'Criando pagamento...',
    'payment_created' => 'Pagamento criado! Redirecionando para página de pagamento...',
    
    // Transactions
    'transactions' => 'Transações',
    'transaction_id' => 'ID da Transação',
    'order_id' => 'ID do Pedido',
    'payment_id' => 'ID do Pagamento',
    'user' => 'Usuário',
    'date' => 'Data',
    'no_transactions' => 'Sem transações',
    
    // Transaction statuses
    'status_pending' => 'Aguardando Pagamento',
    'status_completed' => 'Pago',
    'status_failed' => 'Erro',
    'status_refunded' => 'Reembolso',
    'status_cancelled' => 'Cancelado',
    
    // Actions
    'save' => 'Salvar',
    'cancel' => 'Cancelar',
    'delete' => 'Excluir',
    'edit' => 'Editar',
    'view' => 'Visualizar',
    'close' => 'Fechar',
    'back' => 'Voltar',
    'refresh' => 'Atualizar',
    'search' => 'Pesquisar',
    'filter' => 'Filtro',
    'export' => 'Exportar',
    'action' => 'Ação',
    'actions' => 'Ações',
    
    // Statistics
    'total_transactions' => 'Total de Transações',
    'successful_payments' => 'Pagamentos Bem-Sucedidos',
    'total_amount' => 'Valor Total',
    'today' => 'Hoje',
    'this_week' => 'Esta Semana',
    'this_month' => 'Este Mês',
    'all_time' => 'O Tempo Todo',
    
    // API and Webhook
    'webhook_url' => 'URL do Webhook',
    'webhook_url_hint' => 'Especifique esta URL nas configurações da loja FreeKassa',
    'test_api_connection' => 'Testar Conexão de API',
    'api_connection_success' => 'Conexão de API estabelecida com sucesso',
    'api_connection_failed' => 'Erro na conexão de API',
    'get_balance' => 'Obter Saldo',
    'balance' => 'Saldo',
    'shop_balance' => 'Saldo da Loja',
    
    // Notifications
    'notifications' => 'Notificações',
    'telegram_notify' => 'Notificações do Telegram',
    'email_notify' => 'Notificações por Email',
    'notify_on_payment' => 'Notificar em Pagamentos',
    
    // Security
    'security' => 'Segurança',
    'check_ip' => 'Verificar Endereço IP',
    'check_ip_hint' => 'Verificar endereço IP do remetente do webhook',
    'allowed_ips' => 'Endereços IP Permitidos',
    'allowed_ips_hint' => 'Endereços IP do servidor FreeKassa, separados por vírgula',
    
    // Error messages
    'error_occurred' => 'Ocorreu um erro',
    'error_invalid_data' => 'Dados inválidos',
    'error_instance_not_found' => 'Loja não encontrada',
    'error_api_request_failed' => 'Erro na solicitação de API do FreeKassa',
    'error_invalid_signature' => 'Assinatura inválida',
    'error_invalid_amount' => 'Valor de pagamento inválido',
    'error_missing_credentials' => 'Credenciais de API não especificadas',
    'error_payment_creation_failed' => 'Erro ao criar pagamento',
    'error_webhook_processing_failed' => 'Erro ao processar webhook',
    
    // Success messages
    'success' => 'Sucesso',
    'success_saved' => 'Dados salvos com sucesso',
    'success_deleted' => 'Dados excluídos com sucesso',
    'success_updated' => 'Dados atualizados com sucesso',
    
    // Help and hints
    'help' => 'Ajuda',
    'documentation' => 'Documentação',
    'need_help' => 'Precisa de ajuda?',
    'visit_documentation' => 'Visite a documentação do FreeKassa',
    'support' => 'Suporte',
    
    // Advanced settings
    'advanced_settings' => 'Configurações Avançadas',
    'success_url' => 'URL de Pagamento Bem-Sucedido',
    'failure_url' => 'URL de Pagamento Falhado',
    'notification_url' => 'URL de Notificação',
    'custom_urls' => 'URLs Personalizadas',
    'custom_urls_hint' => 'Substituir URLs padrão (requer permissão de suporte do FreeKassa)',
    
    // Logs
    'logs' => 'Registros',
    'api_logs' => 'Registros de API',
    'view_logs' => 'Visualizar Registros',
    'clear_logs' => 'Limpar Registros',
    'logs_cleared' => 'Registros limpos',
    
    // Tables
    'no_data' => 'Sem dados',
    'loading' => 'Carregando...',
    'total' => 'Total',
    'page' => 'Página',
    'of' => 'de',
    'show' => 'Mostrar',
    'entries' => 'entradas',
    
    // Confirmations
    'are_you_sure' => 'Tem certeza?',
    'confirm_action' => 'Confirmar ação',
    'this_action_cannot_be_undone' => 'Esta ação não pode ser desfeita',
    
    // Additional options
    'settings' => 'Configurações',
    'general_settings' => 'Configurações Gerais',
    'display_settings' => 'Configurações de Exibição',
    'show_in_menu' => 'Mostrar no Menu',
    'priority' => 'Prioridade',
    'order' => 'Ordem',
    'no_payment_methods' => 'Métodos de pagamento disponíveis não encontrados',
    'no_instances' => 'Lojas ativas não encontradas',
    'freekassa_desc' => 'Sistema de pagamento FreeKassa com suporte para múltiplos métodos de pagamento',
    'estimated_cost' => 'Custo estimado',
];
