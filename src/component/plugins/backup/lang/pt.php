<?php

return [
    'backup_manager' => 'Gerenciador de Backup',
    'backup_manager_description' => 'Criar e gerenciar backups do banco de dados e arquivos do site',
    
    // Headers
    'backup_title' => 'Gerenciador de Backup',
    'backup_diagnostics' => 'Diagnóstico do Host',
    'backup_create_new' => 'Criar novo backup',
    'backup_history' => 'Histórico de Backups',
    
    // Buttons
    'backup_btn_create' => 'Criar backup',
    'backup_btn_download' => 'Baixar',
    'backup_btn_delete' => 'Excluir',
    'backup_btn_start' => 'Iniciar',
    'backup_btn_cancel' => 'Cancelar',
    'backup_btn_refresh' => 'Atualizar',
    
    // Types
    'backup_type_db' => 'Apenas Banco de Dados',
    'backup_type_site' => 'Apenas Arquivos do Site',
    'backup_type_db_and_files' => 'Banco de Dados + Arquivos do Site',
    
    // Formats
    'backup_format_zip' => 'Arquivo ZIP',
    'backup_format_gzip' => 'Arquivo GZIP',
    'backup_format_bzip2' => 'Arquivo BZIP2',
    
    // Status messages
    'backup_status_pending' => 'Pendente',
    'backup_status_in_progress' => 'Em andamento',
    'backup_status_completed' => 'Concluído',
    'backup_status_failed' => 'Falhou',
    'backup_status_cancelled' => 'Cancelado',
    
    // Diagnostics labels
    'backup_php_version' => 'Versão do PHP',
    'backup_memory_limit' => 'Limite de Memória',
    'backup_max_execution_time' => 'Tempo Máx. de Execução',
    'backup_available_memory' => 'Memória Disponível',
    'backup_free_disk_space' => 'Espaço Livre em Disco',
    'backup_backup_dir_exists' => 'Diretório de Backups',
    'backup_backup_dir_writable' => 'Diretório de Backups gravável',
    'backup_extensions' => 'Extensões de Arquivo',
    'backup_mysql_version' => 'Versão do MySQL',
    'backup_database_name' => 'Nome do Banco de Dados',
    'backup_database_size' => 'Tamanho do Banco de Dados',
    
    // Info
    'backup_total_backups' => 'Total de Backups',
    'backup_database_size_info' => 'Tamanho do Banco de Dados',
    'backup_files_size_info' => 'Tamanho dos Arquivos do Site',
    'backup_estimated_size' => 'Tamanho Estimado do Backup',
    'backup_available_space' => 'Espaço Disponível',
    
    // Errors
    'backup_error_init' => 'Falha ao inicializar o backup',
    'backup_error_start' => 'Falha ao iniciar o backup',
    'backup_error_invalid_type' => 'Tipo de backup inválido',
    'backup_error_invalid_format' => 'Formato de arquivo inválido',
    'backup_error_task_not_found' => 'Tarefa de backup não encontrada',
    'backup_error_file_not_found' => 'Arquivo de backup não encontrado',
    'backup_error_create_archive' => 'Falha ao criar o arquivo',
    'backup_error_no_space' => 'Espaço em disco insuficiente',
    'backup_error_permission' => 'Permissão negada',
    
    // Success
    'backup_success_created' => 'Backup criado com sucesso',
    'backup_success_deleted' => 'Backup excluído com sucesso',
    'backup_success_download' => 'Download iniciado',
    
    // Table columns
    'backup_col_id' => 'ID',
    'backup_col_type' => 'Tipo',
    'backup_col_format' => 'Formato',
    'backup_col_status' => 'Status',
    'backup_col_progress' => 'Progresso',
    'backup_col_size' => 'Tamanho',
    'backup_col_created' => 'Criado',
    'backup_col_actions' => 'Ações',
    'backup_col_error' => 'Erro',
    
    // Messages
    'backup_msg_select_type' => 'Selecione o que salvar',
    'backup_msg_select_format' => 'Selecione o formato do arquivo',
    'backup_msg_creating' => 'Criando backup...',
    'backup_msg_no_backups' => 'Nenhum backup disponível',
    'backup_msg_confirm_delete' => 'Tem certeza que deseja excluir este backup?',
    'backup_msg_insufficient_space' => 'Espaço em disco insuficiente para o backup',
    'backup_msg_low_memory' => 'Pouca memória disponível - o backup pode demorar mais',
    'backup_msg_server_disabled' => 'Funcionalidade de backup está desabilitada neste servidor',
    
    // Help text
    'backup_help_db_only' => 'Criar backup apenas do banco de dados',
    'backup_help_files_only' => 'Criar backup apenas dos arquivos do site',
    'backup_help_db_files' => 'Criar backup do banco de dados e dos arquivos do site',
    'backup_help_zip' => 'Formato ZIP - amplamente suportado e compatível',
    'backup_help_gzip' => 'Formato GZIP - menor tamanho, requer descompactação',
    'backup_help_bzip2' => 'Formato BZIP2 - melhor compressão, pode demorar mais',
];
