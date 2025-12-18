<?php

return [
    'backup_manager' => 'Менеджер резервных копий',
    'backup_manager_description' => 'Создание и управление резервными копиями базы данных и файлов сайта',
    
    // Headers
    'backup_title' => 'Менеджер резервных копий',
    'backup_diagnostics' => 'Диагностика хостинга',
    'backup_create_new' => 'Создать новый бекап',
    'backup_history' => 'История резервных копий',
    
    // Buttons
    'backup_btn_create' => 'Создать бекап',
    'backup_btn_download' => 'Скачать',
    'backup_btn_delete' => 'Удалить',
    'backup_btn_start' => 'Начать',
    'backup_btn_cancel' => 'Отмена',
    'backup_btn_refresh' => 'Обновить',
    
    // Types
    'backup_type_db' => 'Только база данных',
    'backup_type_site' => 'Только файлы сайта',
    'backup_type_db_and_files' => 'База данных + файлы сайта',
    
    // Formats
    'backup_format_zip' => 'ZIP архив',
    'backup_format_gzip' => 'GZIP архив',
    'backup_format_bzip2' => 'BZIP2 архив',
    
    // Status messages
    'backup_status_pending' => 'Ожидание',
    'backup_status_in_progress' => 'В процессе',
    'backup_status_completed' => 'Завершено',
    'backup_status_failed' => 'Ошибка',
    'backup_status_cancelled' => 'Отменено',
    
    // Diagnostics labels
    'backup_php_version' => 'Версия PHP',
    'backup_memory_limit' => 'Лимит памяти',
    'backup_max_execution_time' => 'Максимальное время выполнения',
    'backup_available_memory' => 'Доступная системная память',
    'backup_free_disk_space' => 'Свободное место на диске',
    'backup_backup_dir_exists' => 'Директория бекапов',
    'backup_backup_dir_writable' => 'Директория бекапов доступна для записи',
    'backup_extensions' => 'Поддерживаемые расширения архивирования',
    'backup_mysql_version' => 'Версия MySQL',
    'backup_database_name' => 'Имя базы данных',
    'backup_database_size' => 'Размер базы данных',
    
    // Info
    'backup_total_backups' => 'Всего бекапов',
    'backup_database_size_info' => 'Размер базы данных',
    'backup_files_size_info' => 'Размер файлов сайта',
    'backup_estimated_size' => 'Примерный размер бекапа',
    'backup_available_space' => 'Доступное место на диске',
    
    // Errors
    'backup_error_init' => 'Ошибка инициализации бекапа',
    'backup_error_start' => 'Ошибка при запуске бекапа',
    'backup_error_invalid_type' => 'Неверный тип бекапа',
    'backup_error_invalid_format' => 'Неверный формат архива',
    'backup_error_task_not_found' => 'Задача бекапа не найдена',
    'backup_error_file_not_found' => 'Файл бекапа не найден',
    'backup_error_create_archive' => 'Ошибка при создании архива',
    'backup_error_no_space' => 'Недостаточно места на диске',
    'backup_error_permission' => 'Доступ запрещен',
    
    // Success
    'backup_success_created' => 'Бекап успешно создан',
    'backup_success_deleted' => 'Бекап успешно удален',
    'backup_success_download' => 'Загрузка началась',
    
    // Table columns
    'backup_col_id' => 'ID',
    'backup_col_type' => 'Тип',
    'backup_col_format' => 'Формат',
    'backup_col_status' => 'Статус',
    'backup_col_progress' => 'Прогресс',
    'backup_col_size' => 'Размер',
    'backup_col_created' => 'Создан',
    'backup_col_actions' => 'Действия',
    'backup_col_error' => 'Ошибка',
    
    // Messages
    'backup_msg_select_type' => 'Выберите что создавать резервную копию',
    'backup_msg_select_format' => 'Выберите формат архива',
    'backup_msg_creating' => 'Создание резервной копии...',
    'backup_msg_no_backups' => 'Нет доступных резервных копий',
    'backup_msg_confirm_delete' => 'Вы уверены что хотите удалить эту резервную копию?',
    'backup_msg_insufficient_space' => 'Недостаточно места на диске для создания бекапа',
    'backup_msg_low_memory' => 'Мало доступной памяти - бекап может занять больше времени',
    'backup_msg_server_disabled' => 'Функциональность резервных копий отключена на этом сервере',
    
    // Help text
    'backup_help_db_only' => 'Создать резервную копию только базы данных',
    'backup_help_files_only' => 'Создать резервную копию только файлов сайта',
    'backup_help_db_files' => 'Создать резервную копию базы данных и файлов сайта',
    'backup_help_zip' => 'ZIP формат - широко поддерживаемый и совместимый',
    'backup_help_gzip' => 'GZIP формат - меньший размер, требует распаковки',
    'backup_help_bzip2' => 'BZIP2 формат - лучшее сжатие, может занять больше времени',
];
