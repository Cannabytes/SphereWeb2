<?php

/**
 * Greek translations for FreeKassa plugin
 */

return [
    // General
    'freekassa' => 'FreeKassa',
    'freekassa_payment_system' => 'Σύστημα Πληρωμών FreeKassa',
    'freekassa_description' => 'Λήψη πληρωμών μέσω FreeKassa με υποστήριξη πολλαπλών καταστημάτων και νομισμάτων',
    'plugin_enabled' => 'Ενεργοποίηση προσθέτου',
    'plugin_disabled' => 'Το σύστημα πληρωμών είναι προσωρινά απενεργοποιημένο',
    'settings_saved' => 'Οι ρυθμίσεις αποθηκεύτηκαν επιτυχώς',
    
    // Admin panel
    'admin_panel' => 'Πίνακας Ελέγχου FreeKassa',
    'plugin_settings' => 'Ρυθμίσεις Προσθέτου',
    'instances_management' => 'Διαχείριση Καταστημάτων',
    'transaction_history' => 'Ιστορικό Συναλλαγών',
    'api_settings' => 'Ρυθμίσεις API',
    'statistics' => 'Στατιστικά',
    
    // Store management (Instances)
    'instances' => 'Καταστήματα',
    'add_instance' => 'Προσθήκη Καταστήματος',
    'edit_instance' => 'Επεξergασία Καταστήματος',
    'delete_instance' => 'Διαγραφή Καταστήματος',
    'instance_name' => 'Όνομα Καταστήματος',
    'instance_description' => 'Περιγραφή Καταστήματος',
    'no_instances' => 'Δεν έχουν δημιουργηθεί καταστήματα',
    'instance_created' => 'Το κατάστημα δημιουργήθηκε με επιτυχία',
    'instance_updated' => 'Το κατάστημα ενημερώθηκε με επιτυχία',
    'instance_deleted' => 'Το κατάστημα διαγράφηκε με επιτυχία',
    'confirm_delete_instance' => 'Είστε σίγουροι ότι θέλετε να διαγράψετε αυτό το κατάστημα;',
    
    // Store settings
    'shop_id' => 'ID Καταστήματος',
    'shop_id_placeholder' => 'Εισαγάγετε το ID καταστήματος FreeKassa',
    'shop_id_hint' => 'Το ID του καταστήματός σας από το προσωπικό λογαριασμό FreeKassa',
    'api_key' => 'Κλειδί API',
    'api_key_placeholder' => 'Εισαγάγετε το κλειδί API',
    'api_key_hint' => 'Κλειδί API από τις ρυθμίσεις καταστήματος FreeKassa',
    'secret_word' => 'Μυστική Λέξη',
    'secret_word_placeholder' => 'Μυστική λέξη για υπογραφή φόρμας',
    'secret_word_hint' => 'Μυστική λέξη από τις ρυθμίσεις καταστήματος (για φόρμα πληρωμής)',
    'secret_word_2' => 'Μυστική Λέξη 2',
    'secret_word_2_placeholder' => 'Μυστική λέξη για επαλήθευση webhook',
    'secret_word_2_hint' => 'Μυστική λέξη 2 από τις ρυθμίσεις καταστήματος (για ειδοποιήσεις)',
    
    // Currencies and payment methods
    'currency' => 'Νόμισμα',
    'currency_default' => 'Προεπιλεγμένο Νόμισμα',
    'payment_method' => 'Μέθοδος Πληρωμής',
    'payment_method_default' => 'Προεπιλεγμένη Μέθοδος Πληρωμής',
    'select_currency' => 'Επιλέξτε Νόμισμα',
    'select_payment_method' => 'Επιλέξτε Μέθοδο Πληρωμής',
    'available_currencies' => 'Διαθέσιμα Νομίσματα',
    'available_payment_methods' => 'Διαθέσιμες Μέθοδοι Πληρωμής',
    'refresh_currencies' => 'Ανανέωση Λίστας Νομισμάτων',
    'currencies_updated' => 'Η λίστα νομισμάτων ενημερώθηκε επιτυχώς',
    'limits' => 'Όρια',
    'fee' => 'Προμήθεια',
    
    // Currencies
    'RUB' => 'Ρούβλι Ρωσίας',
    'USD' => 'Δολάριο ΗΠΑ',
    'EUR' => 'Ευρώ',
    'UAH' => 'Ουκρανική Γρίβνια',
    'KZT' => 'Καζακικό Τεγγέ',
    
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
    'payment_method_13' => 'Online Banking',
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
    'enabled' => 'Ενεργοποιημένο',
    'disabled' => 'Απενεργοποιημένο',
    'status' => 'Κατάσταση',
    'test_mode' => 'Λειτουργία Δοκιμής',
    'test_mode_hint' => 'Στη λειτουργία δοκιμής, οι πληρωμές δεν επεξεργάζονται',
    'active' => 'Ενεργό',
    'inactive' => 'Ανενεργό',
    
    // User section
    'payment_title' => 'Ανεφοδιασμός Υπολοίπου μέσω FreeKassa',
    'payment_subtitle' => 'Επιλέξτε μέθοδο πληρωμής και εισαγάγετε ποσό',
    'select_shop' => 'Επιλέξτε Κατάστημα',
    'amount' => 'Ποσό',
    'amount_placeholder' => 'Εισαγάγετε το ποσό πληρωμής',
    'amount_hint' => 'Ελάχιστο: {min}, Μέγιστο: {max}',
    'min_amount' => 'Ελάχιστο Ποσό',
    'max_amount' => 'Μέγιστο Ποσό',
    'email' => 'Email',
    'email_placeholder' => 'Εισαγάγετε το email σας',
    'phone' => 'Τηλέφωνο',
    'phone_placeholder' => 'Εισαγάγετε το τηλέφωνό σας (προαιρετικό)',
    'pay_button' => 'Μετάβαση στην Πληρωμή',
    'creating_payment' => 'Δημιουργία πληρωμής...',
    'payment_created' => 'Η πληρωμή δημιουργήθηκε! Ανακατεύθυνση στη σελίδα πληρωμής...',
    
    // Transactions
    'transactions' => 'Συναλλαγές',
    'transaction_id' => 'ID Συναλλαγής',
    'order_id' => 'ID Παραγγελίας',
    'payment_id' => 'ID Πληρωμής',
    'user' => 'Χρήστης',
    'date' => 'Ημερομηνία',
    'no_transactions' => 'Δεν υπάρχουν συναλλαγές',
    
    // Transaction statuses
    'status_pending' => 'Αναμονή Πληρωμής',
    'status_completed' => 'Πληρώθηκε',
    'status_failed' => 'Σφάλμα',
    'status_refunded' => 'Επιστροφή',
    'status_cancelled' => 'Ακυρώθηκε',
    
    // Actions
    'save' => 'Αποθήκευση',
    'cancel' => 'Ακύρωση',
    'delete' => 'Διagraφή',
    'edit' => 'Επεξergασία',
    'view' => 'Προβολή',
    'close' => 'Κλείσιμο',
    'back' => 'Πίσω',
    'refresh' => 'Ανανέωση',
    'search' => 'Αναζήτηση',
    'filter' => 'Φίλτρο',
    'export' => 'Εξαγωγή',
    'action' => 'Δράση',
    'actions' => 'Δράσεις',
    
    // Statistics
    'total_transactions' => 'Σύνολο Συναλλαγών',
    'successful_payments' => 'Επιτυχείς Πληρωμές',
    'total_amount' => 'Σύνολο Ποσού',
    'today' => 'Σήμερα',
    'this_week' => 'Αυτή Τιν Εβδομάδα',
    'this_month' => 'Αυτό το Μήνα',
    'all_time' => 'Όλη την Ώρα',
    
    // API and Webhook
    'webhook_url' => 'URL Webhook',
    'webhook_url_hint' => 'Καθορίστε αυτό το URL στις ρυθμίσεις καταστήματος FreeKassa',
    'test_api_connection' => 'Δοκιμή Σύνδεσης API',
    'api_connection_success' => 'Σύνδεση API δημιουργήθηκε με επιτυχία',
    'api_connection_failed' => 'Σφάλμα σύνδεσης API',
    'get_balance' => 'Λήψη Υπολοίπου',
    'balance' => 'Υπόλοιπο',
    'shop_balance' => 'Υπόλοιπο Καταστήματος',
    
    // Notifications
    'notifications' => 'Ειδοποιήσεις',
    'telegram_notify' => 'Ειδοποιήσεις Telegram',
    'email_notify' => 'Ειδοποιήσεις Email',
    'notify_on_payment' => 'Ειδοποίηση σε Πληρωμές',
    
    // Security
    'security' => 'Ασphάλεια',
    'check_ip' => 'Έλεγχος Διεύθυνσης IP',
    'check_ip_hint' => 'Έλεγχος διεύθυνσης IP του αποστολέα webhook',
    'allowed_ips' => 'Επιτρεπόμενες Διευθύνσεις IP',
    'allowed_ips_hint' => 'Διευθύνσεις IP διακομιστή FreeKassa, διαχωρισμένες με κόμμα',
    
    // Error messages
    'error_occurred' => 'Παρουσιάστηκε σφάλμα',
    'error_invalid_data' => 'Μη έγκυρα δεδομένα',
    'error_instance_not_found' => 'Το κατάστημα δεν βρέθηκε',
    'error_api_request_failed' => 'Σφάλμα αιτήματος API FreeKassa',
    'error_invalid_signature' => 'Μη έγκυρη υπογραφή',
    'error_invalid_amount' => 'Μη έγκυρο ποσό πληρωμής',
    'error_missing_credentials' => 'Δεν έχουν καθοριστεί διαπιστευτήρια API',
    'error_payment_creation_failed' => 'Σφάλμα δημιουργίας πληρωμής',
    'error_webhook_processing_failed' => 'Σφάλμα επεξergασίας webhook',
    
    // Success messages
    'success' => 'Επιτυχία',
    'success_saved' => 'Τα δεδομένα αποθηκεύτηκαν με επιτυχία',
    'success_deleted' => 'Τα δεδομένα διαγράφηκαν με επιτυχία',
    'success_updated' => 'Τα δεδομένα ενημερώθηκαν με επιτυχία',
    
    // Help and hints
    'help' => 'Βοήθεια',
    'documentation' => 'Τεκμηρίωση',
    'need_help' => 'Χρειάζεστε βοήθεια;',
    'visit_documentation' => 'Επισκεφθείτε την τεκμηρίωση FreeKassa',
    'support' => 'Υποστήριξη',
    
    // Advanced settings
    'advanced_settings' => 'Σύνθετες Ρυθμίσεις',
    'success_url' => 'URL Επιτυχούς Πληρωμής',
    'failure_url' => 'URL Αποτυχημένης Πληρωμής',
    'notification_url' => 'URL Ειδοποίησης',
    'custom_urls' => 'Προσαρμοσμένα URLs',
    'custom_urls_hint' => 'Αντικατάσταση προεπιλεγμένων URLs (απαιτεί άδεια υποστήριξης FreeKassa)',
    
    // Logs
    'logs' => 'Αρχεία Καταγραφής',
    'api_logs' => 'Αρχεία Καταγραφής API',
    'view_logs' => 'Προβολή Αρχείων Καταγραφής',
    'clear_logs' => 'Εκκαθάριση Αρχείων Καταγραφής',
    'logs_cleared' => 'Τα αρχεία καταγραφής εκκαθαρίστηκαν',
    
    // Tables
    'no_data' => 'Δεν υπάρχουν δεδομένα',
    'loading' => 'Φόρτωση...',
    'total' => 'Σύνολο',
    'page' => 'Σελίδα',
    'of' => 'από',
    'show' => 'Εμφάνιση',
    'entries' => 'καταχωρήσεις',
    
    // Confirmations
    'are_you_sure' => 'Είστε σίγουρες;',
    'confirm_action' => 'Επιβεβαίωση δράσης',
    'this_action_cannot_be_undone' => 'Αυτή η δράση δεν μπορεί να αναιρεθεί',
    
    // Additional options
    'settings' => 'Ρυθμίσεις',
    'general_settings' => 'Γενικές Ρυθμίσεις',
    'display_settings' => 'Ρυθμίσεις Εμφάνισης',
    'show_in_menu' => 'Εμφάνιση στο Μενού',
    'priority' => 'Προτεραιότητα',
    'order' => 'Παραγγελία',
    'no_payment_methods' => 'Δεν βρέθηκαν διαθέσιμες μέθοδοι πληρωμής',
    'no_instances' => 'Δεν βρέθηκαν ενεργά καταστήματα',
    'freekassa_desc' => 'Σύστημα πληρωμών FreeKassa με υποστήριξη πολλαπλών μεθόδων πληρωμής',
    'estimated_cost' => 'Εκτιμώμενο κόστος',
];
