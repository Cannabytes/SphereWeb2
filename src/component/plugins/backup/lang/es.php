<?php

return [
    'backup_manager' => 'Gestor de Copias de Seguridad',
    'backup_manager_description' => 'Crear y gestionar copias de seguridad de la base de datos y archivos del sitio',
    
    // Headers
    'backup_title' => 'Gestor de Copias de Seguridad',
    'backup_diagnostics' => 'Diagnóstico del Host',
    'backup_create_new' => 'Crear nueva copia',
    'backup_history' => 'Historial de Copias',
    
    // Buttons
    'backup_btn_create' => 'Crear copia',
    'backup_btn_download' => 'Descargar',
    'backup_btn_delete' => 'Eliminar',
    'backup_btn_start' => 'Iniciar',
    'backup_btn_cancel' => 'Cancelar',
    'backup_btn_refresh' => 'Actualizar',
    
    // Types
    'backup_type_db' => 'Solo Base de Datos',
    'backup_type_site' => 'Solo Archivos del Sitio',
    'backup_type_db_and_files' => 'Base de Datos + Archivos del Sitio',
    
    // Formats
    'backup_format_zip' => 'Archivo ZIP',
    'backup_format_gzip' => 'Archivo GZIP',
    'backup_format_bzip2' => 'Archivo BZIP2',
    
    // Status messages
    'backup_status_pending' => 'Pendiente',
    'backup_status_in_progress' => 'En progreso',
    'backup_status_completed' => 'Completado',
    'backup_status_failed' => 'Fallado',
    'backup_status_cancelled' => 'Cancelado',
    
    // Diagnostics labels
    'backup_php_version' => 'Versión de PHP',
    'backup_memory_limit' => 'Límite de Memoria',
    'backup_max_execution_time' => 'Tiempo Máx. de Ejecución',
    'backup_available_memory' => 'Memoria Disponible',
    'backup_free_disk_space' => 'Espacio Libre en Disco',
    'backup_backup_dir_exists' => 'Directorio de Copias',
    'backup_backup_dir_writable' => 'Directorio de Copias escribible',
    'backup_extensions' => 'Extensiones de Archivo',
    'backup_mysql_version' => 'Versión de MySQL',
    'backup_database_name' => 'Nombre de la Base de Datos',
    'backup_database_size' => 'Tamaño de la Base de Datos',
    
    // Info
    'backup_total_backups' => 'Copias Totales',
    'backup_database_size_info' => 'Tamaño de la Base de Datos',
    'backup_files_size_info' => 'Tamaño de Archivos del Sitio',
    'backup_estimated_size' => 'Tamaño Estimado de la Copia',
    'backup_available_space' => 'Espacio Disponible',
    
    // Errors
    'backup_error_init' => 'Fallo al inicializar la copia',
    'backup_error_start' => 'Fallo al iniciar la copia',
    'backup_error_invalid_type' => 'Tipo de copia no válido',
    'backup_error_invalid_format' => 'Formato de archivo no válido',
    'backup_error_task_not_found' => 'Tarea de copia no encontrada',
    'backup_error_file_not_found' => 'Archivo de copia no encontrado',
    'backup_error_create_archive' => 'Fallo al crear el archivo',
    'backup_error_no_space' => 'Espacio en disco insuficiente',
    'backup_error_permission' => 'Permiso denegado',
    
    // Success
    'backup_success_created' => 'Copia creada correctamente',
    'backup_success_deleted' => 'Copia eliminada correctamente',
    'backup_success_download' => 'Descarga iniciada',
    
    // Table columns
    'backup_col_id' => 'ID',
    'backup_col_type' => 'Tipo',
    'backup_col_format' => 'Formato',
    'backup_col_status' => 'Estado',
    'backup_col_progress' => 'Progreso',
    'backup_col_size' => 'Tamaño',
    'backup_col_created' => 'Creado',
    'backup_col_actions' => 'Acciones',
    'backup_col_error' => 'Error',
    
    // Messages
    'backup_msg_select_type' => 'Seleccione qué copiar',
    'backup_msg_select_format' => 'Seleccione el formato del archivo',
    'backup_msg_creating' => 'Creando copia...',
    'backup_msg_no_backups' => 'No hay copias disponibles',
    'backup_msg_confirm_delete' => '¿Está seguro de que desea eliminar esta copia?',
    'backup_msg_insufficient_space' => 'Espacio en disco insuficiente para la copia',
    'backup_msg_low_memory' => 'Memoria disponible baja - la copia puede tardar más',
    'backup_msg_server_disabled' => 'La funcionalidad de copias está deshabilitada en este servidor',
    
    // Help text
    'backup_help_db_only' => 'Crear copia solo de la base de datos',
    'backup_help_files_only' => 'Crear copia solo de los archivos del sitio',
    'backup_help_db_files' => 'Crear copia de la base de datos y los archivos del sitio',
    'backup_help_zip' => 'Formato ZIP - ampliamente soportado y compatible',
    'backup_help_gzip' => 'Formato GZIP - menor tamaño, requiere descompresión',
    'backup_help_bzip2' => 'Formato BZIP2 - mejor compresión, puede tardar más',
];
