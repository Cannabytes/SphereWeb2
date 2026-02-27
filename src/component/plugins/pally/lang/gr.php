<?php

return [
    'pally_title' => 'Pally',
    'pally_description' => 'Σύστημα πληρωμών Pally για πληρωμή μέσω διαφόρων μεθόδων πληρωμής',
    'pally_payment_title' => 'Φόρτιση μέσω Pally',
    'pally_payment_description' => 'Εισάγετε το ποσό και προχωρήστε στην πληρωμή',
    'pally_amount_label' => 'Ποσό',
    'pally_pay_button' => 'Προχώρηση στην πληρωμή',
    'pally_min_max' => 'Ελάχιστο: %s, Μέγιστο: %s',
    'pally_minimum' => 'Ελάχιστο',
    'pally_maximum' => 'Μέγιστο',
    
    // Admin panel — πύλες
    'pally_gateways_title'            => 'Πύλες πληρωμής',
    'pally_add_gateway'               => 'Προσθήκη πύλης',
    'pally_gateway_label'             => 'Περιγραφή',
    'pally_gateway_label_placeholder' => 'π.χ. Visa/Mastercard',
    'pally_action_label'              => 'Ενέργεια',
    'pally_remove_gateway'            => 'Αφαίρεση',
    'pally_gateways_description'      => 'Κάθε πύλη μπορεί να έχει το δικό της API Key, Shop ID, νόμισμα και περιγραφή.',
    'pally_select_gateway'            => 'Επιλέξτε πύλη πληρωμής',

    // Admin panel
    'pally_admin_title' => 'Pally',
    'pally_admin_description' => 'Διαχείριση ταμείου πληρωμών Pally',
    'pally_activate_plugin' => 'Ενεργοποίηση προσθέτου',
    'pally_plugin_disabled' => 'Το πρόσθετο είναι απενεργοποιημένο. Ενεργοποιήστε την εναλλαγή παραπάνω.',
    'pally_webhook_url' => 'Webhook URL',
    'pally_webhook_copy' => 'Αντιγραφή',
    'pally_webhook_note' => 'Αντιγράψτε και καθορίστε αυτήν την διεύθυνση URL στον λογαριασμό σας Pally.',
    'pally_shop_id' => 'Αναγνωριστικό Καταστήματος',
    'pally_api_key' => 'API Key',
    'pally_currency' => 'Νόμισμα',
    'pally_supported_countries' => 'Υποστηριζόμενες χώρες',
    'pally_save_settings' => 'Αποθήκευση ρυθμίσεων',
    
    // Messages
    'pally_fill_credentials' => 'Προσθέστε τουλάχιστον μία πύλη με shop_id και api_key',
    'pally_settings_saved' => 'Οι ρυθμίσεις του Pally αποθηκεύτηκαν',
    'pally_not_configured' => 'Το Pally δεν έχει ρυθμιστεί. Επικοινωνήστε με τον διαχειριστή.',
    'pally_enter_amount' => 'Εισάγετε το ποσό ως αριθμό',
    'pally_not_configured_admin' => 'Το Pally δεν έχει ρυθμιστεί',
    'pally_min_amount' => 'Ελάχιστη φόρτιση: %s',
    'pally_max_amount' => 'Μέγιστη φόρτιση: %s',
    'pally_payment_creating' => 'Δημιουργία πληρωμής...',
    'pally_curl_error' => 'Σφάλμα CURL Pally: %s',
    'pally_invalid_response' => 'Άκυρη απάντηση του Pally',
    'pally_api_error' => 'Σφάλμα Pally: %s',
    'pally_no_payment_link' => 'Το Pally δεν επέστρεψε σύνδεσμο πληρωμής',
    'pally_payment_error' => 'Άγνωστο σφάλμα κατά τη δημιουργία πληρωμής',
    'pally_connection_error' => 'Σφάλμα σύνδεσης',
];
