<?php

return [
    'backup_manager' => 'Διαχείριση Αντιγράφων',
    'backup_manager_description' => 'Δημιουργία και διαχείριση αντιγράφων ασφαλείας της βάσης δεδομένων και των αρχείων του ιστότοπου',
    
    // Headers
    'backup_title' => 'Διαχείριση Αντιγράφων',
    'backup_diagnostics' => 'Διαγνωστικός Έλεγχος Φιλοξενίας',
    'backup_create_new' => 'Δημιουργία νέου αντιγράφου',
    'backup_history' => 'Ιστορικό Αντιγράφων',
    
    // Buttons
    'backup_btn_create' => 'Δημιουργία',
    'backup_btn_download' => 'Λήψη',
    'backup_btn_delete' => 'Διαγραφή',
    'backup_btn_start' => 'Έναρξη',
    'backup_btn_cancel' => 'Άκυρο',
    'backup_btn_refresh' => 'Ανανέωση',
    
    // Types
    'backup_type_db' => 'Μόνο Βάση Δεδομένων',
    'backup_type_site' => 'Μόνο Αρχεία Ιστοτόπου',
    'backup_type_db_and_files' => 'Βάση Δεδομένων + Αρχεία Ιστοτόπου',
    
    // Formats
    'backup_format_zip' => 'Αρχείο ZIP',
    'backup_format_gzip' => 'Αρχείο GZIP',
    'backup_format_bzip2' => 'Αρχείο BZIP2',
    
    // Status messages
    'backup_status_pending' => 'Σε αναμονή',
    'backup_status_in_progress' => 'Σε εξέλιξη',
    'backup_status_completed' => 'Ολοκληρώθηκε',
    'backup_status_failed' => 'Απέτυχε',
    'backup_status_cancelled' => 'Ακυρώθηκε',
    
    // Diagnostics labels
    'backup_php_version' => 'Έκδοση PHP',
    'backup_memory_limit' => 'Όριο Μνήμης',
    'backup_max_execution_time' => 'Μέγιστος Χρόνος Εκτέλεσης',
    'backup_available_memory' => 'Διαθέσιμη Μνήμη Συστήματος',
    'backup_free_disk_space' => 'Ελεύθερος Χώρος Δίσκου',
    'backup_backup_dir_exists' => 'Φάκελος Αντιγράφων',
    'backup_backup_dir_writable' => 'Φάκελος Αντιγράφων εγγράψιμος',
    'backup_extensions' => 'Επεκτάσεις Αρχείων',
    'backup_mysql_version' => 'Έκδοση MySQL',
    'backup_database_name' => 'Όνομα Βάσης Δεδομένων',
    'backup_database_size' => 'Μέγεθος Βάσης Δεδομένων',
    
    // Info
    'backup_total_backups' => 'Συνολικά Αντίγραφα',
    'backup_database_size_info' => 'Μέγεθος Βάσης Δεδομένων',
    'backup_files_size_info' => 'Μέγεθος Αρχείων Ιστοτόπου',
    'backup_estimated_size' => 'Εκτιμώμενο Μέγεθος Αντιγράφου',
    'backup_available_space' => 'Διαθέσιμος Χώρος',
    
    // Errors
    'backup_error_init' => 'Αποτυχία αρχικοποίησης αντιγράφου',
    'backup_error_start' => 'Αποτυχία έναρξης αντιγράφου',
    'backup_error_invalid_type' => 'Μη έγκυρος τύπος αντιγράφου',
    'backup_error_invalid_format' => 'Μη έγκυρος μορφότυπος αρχείου',
    'backup_error_task_not_found' => 'Η εργασία αντιγράφου δεν βρέθηκε',
    'backup_error_file_not_found' => 'Το αρχείο αντιγράφου δεν βρέθηκε',
    'backup_error_create_archive' => 'Αποτυχία δημιουργίας αρχείου',
    'backup_error_no_space' => 'Ανεπαρκής χώρος στο δίσκο',
    'backup_error_permission' => 'Άρνηση πρόσβασης',
    
    // Success
    'backup_success_created' => 'Το αντίγραφο δημιουργήθηκε με επιτυχία',
    'backup_success_deleted' => 'Το αντίγραφο διαγράφηκε με επιτυχία',
    'backup_success_download' => 'Η λήψη ξεκίνησε',
    
    // Table columns
    'backup_col_id' => 'ID',
    'backup_col_type' => 'Τύπος',
    'backup_col_format' => 'Μορφή',
    'backup_col_status' => 'Κατάσταση',
    'backup_col_progress' => 'Πρόοδος',
    'backup_col_size' => 'Μέγεθος',
    'backup_col_created' => 'Δημιουργήθηκε',
    'backup_col_actions' => 'Ενέργειες',
    'backup_col_error' => 'Σφάλμα',
    
    // Messages
    'backup_msg_select_type' => 'Επιλέξτε τι θα αντιγραφεί',
    'backup_msg_select_format' => 'Επιλέξτε μορφή αρχείου',
    'backup_msg_creating' => 'Δημιουργία αντιγράφου...',
    'backup_msg_no_backups' => 'Δεν υπάρχουν διαθέσιμα αντίγραφα',
    'backup_msg_confirm_delete' => 'Είστε βέβαιοι ότι θέλετε να διαγράψετε αυτό το αντίγραφο;',
    'backup_msg_insufficient_space' => 'Ανεπαρκής χώρος στο δίσκο για το αντίγραφο',
    'backup_msg_low_memory' => 'Λιγότερη διαθέσιμη μνήμη - το αντίγραφο μπορεί να διαρκέσει περισσότερο',
    'backup_msg_server_disabled' => 'Η λειτουργία αντιγράφων είναι απενεργοποιημένη σε αυτόν τον διακομιστή',
    
    // Help text
    'backup_help_db_only' => 'Δημιουργία αντιγράφου μόνο της βάσης δεδομένων',
    'backup_help_files_only' => 'Δημιουργία αντιγράφου μόνο των αρχείων του ιστότοπου',
    'backup_help_db_files' => 'Δημιουργία αντιγράφου της βάσης και των αρχείων του ιστότοπου',
    'backup_help_zip' => 'Μορφή ZIP - ευρέως υποστηριζόμενη και συμβατή',
    'backup_help_gzip' => 'Μορφή GZIP - μικρότερο μέγεθος, απαιτεί αποσυμπίεση',
    'backup_help_bzip2' => 'Μορφή BZIP2 - καλύτερη συμπίεση, μπορεί να διαρκέσει περισσότερο',
];
