<?php

return [
    'backup_manager' => 'Менеджер резервних копій',
    'backup_manager_description' => 'Створення та керування резервними копіями бази даних та файлів сайту',
    
    // Headers
    'backup_title' => 'Менеджер резервних копій',
    'backup_diagnostics' => 'Діагностика хостингу',
    'backup_create_new' => 'Створити нову копію',
    'backup_history' => 'Історія резервних копій',
    
    // Buttons
    'backup_btn_create' => 'Створити копію',
    'backup_btn_download' => 'Завантажити',
    'backup_btn_delete' => 'Видалити',
    'backup_btn_start' => 'Почати',
    'backup_btn_cancel' => 'Скасувати',
    'backup_btn_refresh' => 'Оновити',
    
    // Types
    'backup_type_db' => 'Тільки база даних',
    'backup_type_site' => 'Тільки файли сайту',
    'backup_type_db_and_files' => 'База даних + файли сайту',
    
    // Formats
    'backup_format_zip' => 'ZIP архів',
    'backup_format_gzip' => 'GZIP архів',
    'backup_format_bzip2' => 'BZIP2 архів',
    
    // Status messages
    'backup_status_pending' => 'Очікування',
    'backup_status_in_progress' => 'У процесі',
    'backup_status_completed' => 'Завершено',
    'backup_status_failed' => 'Помилка',
    'backup_status_cancelled' => 'Скасовано',
    
    // Diagnostics labels
    'backup_php_version' => 'Версія PHP',
    'backup_memory_limit' => 'Ліміт пам’яті',
    'backup_max_execution_time' => 'Макс. час виконання',
    'backup_available_memory' => 'Доступна пам’ять системи',
    'backup_free_disk_space' => 'Вільне місце на диску',
    'backup_backup_dir_exists' => 'Каталог резервних копій',
    'backup_backup_dir_writable' => 'Каталог резервних копій доступний для запису',
    'backup_extensions' => 'Розширення архівування',
    'backup_mysql_version' => 'Версія MySQL',
    'backup_database_name' => 'Ім’я бази даних',
    'backup_database_size' => 'Розмір бази даних',
    
    // Info
    'backup_total_backups' => 'Всього копій',
    'backup_database_size_info' => 'Розмір бази даних',
    'backup_files_size_info' => 'Розмір файлів сайту',
    'backup_estimated_size' => 'Орієнтовний розмір копії',
    'backup_available_space' => 'Доступне місце',
    
    // Errors
    'backup_error_init' => 'Не вдалося ініціалізувати копію',
    'backup_error_start' => 'Не вдалося запустити копію',
    'backup_error_invalid_type' => 'Неправильний тип копії',
    'backup_error_invalid_format' => 'Неправильний формат архіву',
    'backup_error_task_not_found' => 'Завдання копіювання не знайдено',
    'backup_error_file_not_found' => 'Файл резервної копії не знайдено',
    'backup_error_create_archive' => 'Не вдалося створити архів',
    'backup_error_no_space' => 'Немає достатньо місця на диску',
    'backup_error_permission' => 'Доступ заборонено',
    
    // Success
    'backup_success_created' => 'Копію успішно створено',
    'backup_success_deleted' => 'Копію успішно видалено',
    'backup_success_download' => 'Завантаження розпочато',
    
    // Table columns
    'backup_col_id' => 'ID',
    'backup_col_type' => 'Тип',
    'backup_col_format' => 'Формат',
    'backup_col_status' => 'Статус',
    'backup_col_progress' => 'Прогрес',
    'backup_col_size' => 'Розмір',
    'backup_col_created' => 'Створено',
    'backup_col_actions' => 'Дії',
    'backup_col_error' => 'Помилка',
    
    // Messages
    'backup_msg_select_type' => 'Виберіть, що зберегти',
    'backup_msg_select_format' => 'Виберіть формат архіву',
    'backup_msg_creating' => 'Створення резервної копії...',
    'backup_msg_no_backups' => 'Немає доступних копій',
    'backup_msg_confirm_delete' => 'Ви впевнені, що хочете видалити цю копію?',
    'backup_msg_insufficient_space' => 'Недостатньо місця на диску для копії',
    'backup_msg_low_memory' => 'Мало доступної пам’яті — копія може зайняти більше часу',
    'backup_msg_server_disabled' => 'Функція резервного копіювання вимкнена на цьому сервері',
    
    // Help text
    'backup_help_db_only' => 'Створити резервну копію тільки бази даних',
    'backup_help_files_only' => 'Створити резервну копію тільки файлів сайту',
    'backup_help_db_files' => 'Створити резервну копію бази даних та файлів сайту',
    'backup_help_zip' => 'Формат ZIP — широко підтримується і сумісний',
    'backup_help_gzip' => 'Формат GZIP — менший розмір, потребує розпакування',
    'backup_help_bzip2' => 'Формат BZIP2 — краще стиснення, може зайняти більше часу',
];
