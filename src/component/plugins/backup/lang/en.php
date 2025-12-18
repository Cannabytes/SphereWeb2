<?php

return [
    'backup_manager' => 'Backup Manager',
    'backup_manager_description' => 'Create and manage backups of your database and website files',
    
    // Headers
    'backup_title' => 'Backup Manager',
    'backup_diagnostics' => 'Host Diagnostics',
    'backup_create_new' => 'Create New Backup',
    'backup_history' => 'Backup History',
    
    // Buttons
    'backup_btn_create' => 'Create Backup',
    'backup_btn_download' => 'Download',
    'backup_btn_delete' => 'Delete',
    'backup_btn_start' => 'Start',
    'backup_btn_cancel' => 'Cancel',
    'backup_btn_refresh' => 'Refresh',
    
    // Types
    'backup_type_db' => 'Database Only',
    'backup_type_site' => 'Website Files Only',
    'backup_type_db_and_files' => 'Database + Website Files',
    
    // Formats
    'backup_format_zip' => 'ZIP Archive',
    'backup_format_gzip' => 'GZIP Archive',
    'backup_format_bzip2' => 'BZIP2 Archive',
    
    // Status messages
    'backup_status_pending' => 'Pending',
    'backup_status_in_progress' => 'In Progress',
    'backup_status_completed' => 'Completed',
    'backup_status_failed' => 'Failed',
    'backup_status_cancelled' => 'Cancelled',
    
    // Diagnostics labels
    'backup_php_version' => 'PHP Version',
    'backup_memory_limit' => 'Memory Limit',
    'backup_max_execution_time' => 'Max Execution Time',
    'backup_available_memory' => 'Available System Memory',
    'backup_free_disk_space' => 'Free Disk Space',
    'backup_backup_dir_exists' => 'Backup Directory',
    'backup_backup_dir_writable' => 'Backup Directory Writable',
    'backup_extensions' => 'Archive Extensions',
    'backup_mysql_version' => 'MySQL Version',
    'backup_database_name' => 'Database Name',
    'backup_database_size' => 'Database Size',
    
    // Info
    'backup_total_backups' => 'Total Backups',
    'backup_database_size_info' => 'Database Size',
    'backup_files_size_info' => 'Website Files Size',
    'backup_estimated_size' => 'Estimated Backup Size',
    'backup_available_space' => 'Available Disk Space',
    
    // Errors
    'backup_error_init' => 'Failed to initialize backup',
    'backup_error_start' => 'Failed to start backup',
    'backup_error_invalid_type' => 'Invalid backup type',
    'backup_error_invalid_format' => 'Invalid archive format',
    'backup_error_task_not_found' => 'Backup task not found',
    'backup_error_file_not_found' => 'Backup file not found',
    'backup_error_create_archive' => 'Failed to create archive',
    'backup_error_no_space' => 'Not enough disk space',
    'backup_error_permission' => 'Permission denied',
    
    // Success
    'backup_success_created' => 'Backup created successfully',
    'backup_success_deleted' => 'Backup deleted successfully',
    'backup_success_download' => 'Download started',
    
    // Table columns
    'backup_col_id' => 'ID',
    'backup_col_type' => 'Type',
    'backup_col_format' => 'Format',
    'backup_col_status' => 'Status',
    'backup_col_progress' => 'Progress',
    'backup_col_size' => 'Size',
    'backup_col_created' => 'Created',
    'backup_col_actions' => 'Actions',
    'backup_col_error' => 'Error',
    
    // Messages
    'backup_msg_select_type' => 'Select what to backup',
    'backup_msg_select_format' => 'Select archive format',
    'backup_msg_creating' => 'Creating backup...',
    'backup_msg_no_backups' => 'No backups available',
    'backup_msg_confirm_delete' => 'Are you sure you want to delete this backup?',
    'backup_msg_insufficient_space' => 'Insufficient disk space for backup',
    'backup_msg_low_memory' => 'Low available memory - backup may take longer',
    'backup_msg_server_disabled' => 'Backup functionality is currently disabled on this server',
    
    // Help text
    'backup_help_db_only' => 'Create a backup of the database only',
    'backup_help_files_only' => 'Create a backup of website files only',
    'backup_help_db_files' => 'Create a backup of both database and website files',
    'backup_help_zip' => 'ZIP format - widely supported and compatible',
    'backup_help_gzip' => 'GZIP format - smaller size, requires extraction',
    'backup_help_bzip2' => 'BZIP2 format - best compression, may take longer',
];
