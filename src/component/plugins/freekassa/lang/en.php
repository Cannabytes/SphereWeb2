<?php

/**
 * English translations for FreeKassa plugin
 */

return [
    // General
    'freekassa' => 'FreeKassa',
    'freekassa_payment_system' => 'FreeKassa Payment System',
    'freekassa_description' => 'Payment reception through FreeKassa with support for multiple stores and currencies',
    'plugin_enabled' => 'Enable plugin',
    'plugin_disabled' => 'Payment system is temporarily disabled',
    'settings_saved' => 'Settings saved successfully',
    
    // Admin panel
    'admin_panel' => 'FreeKassa Control Panel',
    'plugin_settings' => 'Plugin Settings',
    'instances_management' => 'Store Management',
    'transaction_history' => 'Transaction History',
    'api_settings' => 'API Settings',
    'statistics' => 'Statistics',
    
    // Store management (Instances)
    'instances' => 'Stores',
    'add_instance' => 'Add Store',
    'edit_instance' => 'Edit Store',
    'delete_instance' => 'Delete Store',
    'instance_name' => 'Store Name',
    'instance_description' => 'Store Description',
    'no_instances' => 'No stores created',
    'instance_created' => 'Store created successfully',
    'instance_updated' => 'Store updated successfully',
    'instance_deleted' => 'Store deleted successfully',
    'confirm_delete_instance' => 'Are you sure you want to delete this store?',
    
    // Store settings
    'shop_id' => 'Store ID',
    'shop_id_placeholder' => 'Enter FreeKassa store ID',
    'shop_id_hint' => 'Your store ID from FreeKassa personal account',
    'api_key' => 'API Key',
    'api_key_placeholder' => 'Enter API key',
    'api_key_hint' => 'API key from FreeKassa store settings',
    'secret_word' => 'Secret Word',
    'secret_word_placeholder' => 'Secret word for form signature',
    'secret_word_hint' => 'Secret word from store settings (for payment form)',
    'secret_word_2' => 'Secret Word 2',
    'secret_word_2_placeholder' => 'Secret word for webhook verification',
    'secret_word_2_hint' => 'Secret word 2 from store settings (for notifications)',
    
    // Currencies and payment methods
    'currency' => 'Currency',
    'currency_default' => 'Default Currency',
    'payment_method' => 'Payment Method',
    'payment_method_default' => 'Default Payment Method',
    'select_currency' => 'Select Currency',
    'select_payment_method' => 'Select Payment Method',
    'available_currencies' => 'Available Currencies',
    'available_payment_methods' => 'Available Payment Methods',
    'refresh_currencies' => 'Refresh Currency List',
    'currencies_updated' => 'Currency list updated successfully',
    'limits' => 'Limits',
    'fee' => 'Commission',
    
    // Currencies
    'RUB' => 'Russian Ruble',
    'USD' => 'US Dollar',
    'EUR' => 'Euro',
    'UAH' => 'Ukrainian Hryvnia',
    'KZT' => 'Kazakhstani Tenge',
    
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
    'enabled' => 'Enabled',
    'disabled' => 'Disabled',
    'status' => 'Status',
    'test_mode' => 'Test Mode',
    'test_mode_hint' => 'In test mode, payments are not processed',
    'active' => 'Active',
    'inactive' => 'Inactive',
    
    // User section
    'payment_title' => 'Balance Replenishment via FreeKassa',
    'payment_subtitle' => 'Select payment method and enter amount',
    'select_shop' => 'Select Store',
    'amount' => 'Amount',
    'amount_placeholder' => 'Enter payment amount',
    'amount_hint' => 'Minimum: {min}, Maximum: {max}',
    'min_amount' => 'Minimum Amount',
    'max_amount' => 'Maximum Amount',
    'email' => 'Email',
    'email_placeholder' => 'Enter your email',
    'phone' => 'Phone',
    'phone_placeholder' => 'Enter your phone (optional)',
    'pay_button' => 'Go to Payment',
    'creating_payment' => 'Creating payment...',
    'payment_created' => 'Payment created! Redirecting to payment page...',
    
    // Transactions
    'transactions' => 'Transactions',
    'transaction_id' => 'Transaction ID',
    'order_id' => 'Order ID',
    'payment_id' => 'Payment ID',
    'user' => 'User',
    'date' => 'Date',
    'no_transactions' => 'No transactions',
    
    // Transaction statuses
    'status_pending' => 'Awaiting Payment',
    'status_completed' => 'Paid',
    'status_failed' => 'Error',
    'status_refunded' => 'Refund',
    'status_cancelled' => 'Cancelled',
    
    // Actions
    'save' => 'Save',
    'cancel' => 'Cancel',
    'delete' => 'Delete',
    'edit' => 'Edit',
    'view' => 'View',
    'close' => 'Close',
    'back' => 'Back',
    'refresh' => 'Refresh',
    'search' => 'Search',
    'filter' => 'Filter',
    'export' => 'Export',
    'action' => 'Action',
    'actions' => 'Actions',
    
    // Statistics
    'total_transactions' => 'Total Transactions',
    'successful_payments' => 'Successful Payments',
    'total_amount' => 'Total Amount',
    'today' => 'Today',
    'this_week' => 'This Week',
    'this_month' => 'This Month',
    'all_time' => 'All Time',
    
    // API and Webhook
    'webhook_url' => 'Webhook URL',
    'webhook_url_hint' => 'Specify this URL in FreeKassa store settings',
    'test_api_connection' => 'Test API Connection',
    'api_connection_success' => 'API connection established successfully',
    'api_connection_failed' => 'API connection failed',
    'get_balance' => 'Get Balance',
    'balance' => 'Balance',
    'shop_balance' => 'Store Balance',
    
    // Notifications
    'notifications' => 'Notifications',
    'telegram_notify' => 'Telegram Notifications',
    'email_notify' => 'Email Notifications',
    'notify_on_payment' => 'Notify on Payments',
    
    // Security
    'security' => 'Security',
    'check_ip' => 'Check IP Address',
    'check_ip_hint' => 'Check IP address of webhook sender',
    'allowed_ips' => 'Allowed IP Addresses',
    'allowed_ips_hint' => 'FreeKassa server IP addresses, comma separated',
    
    // Error messages
    'error_occurred' => 'An error occurred',
    'error_invalid_data' => 'Invalid data',
    'error_instance_not_found' => 'Store not found',
    'error_api_request_failed' => 'FreeKassa API request error',
    'error_invalid_signature' => 'Invalid signature',
    'error_invalid_amount' => 'Invalid payment amount',
    'error_missing_credentials' => 'API credentials not specified',
    'error_payment_creation_failed' => 'Payment creation error',
    'error_webhook_processing_failed' => 'Webhook processing error',
    
    // Success messages
    'success' => 'Success',
    'success_saved' => 'Data saved successfully',
    'success_deleted' => 'Data deleted successfully',
    'success_updated' => 'Data updated successfully',
    
    // Help and hints
    'help' => 'Help',
    'documentation' => 'Documentation',
    'need_help' => 'Need help?',
    'visit_documentation' => 'Visit FreeKassa documentation',
    'support' => 'Support',
    
    // Advanced settings
    'advanced_settings' => 'Advanced Settings',
    'success_url' => 'Success Payment URL',
    'failure_url' => 'Failed Payment URL',
    'notification_url' => 'Notification URL',
    'custom_urls' => 'Custom URLs',
    'custom_urls_hint' => 'Override default URLs (requires FreeKassa support permission)',
    
    // Logs
    'logs' => 'Logs',
    'api_logs' => 'API Logs',
    'view_logs' => 'View Logs',
    'clear_logs' => 'Clear Logs',
    'logs_cleared' => 'Logs cleared',
    
    // Tables
    'no_data' => 'No data',
    'loading' => 'Loading...',
    'total' => 'Total',
    'page' => 'Page',
    'of' => 'of',
    'show' => 'Show',
    'entries' => 'entries',
    
    // Confirmations
    'are_you_sure' => 'Are you sure?',
    'confirm_action' => 'Confirm action',
    'this_action_cannot_be_undone' => 'This action cannot be undone',
    
    // Additional options
    'settings' => 'Settings',
    'general_settings' => 'General Settings',
    'display_settings' => 'Display Settings',
    'show_in_menu' => 'Show in Menu',
    'priority' => 'Priority',
    'order' => 'Order',
    'no_payment_methods' => 'Available payment methods not found',
    'no_instances' => 'Active stores not found',
    'freekassa_desc' => 'FreeKassa payment system with support for multiple payment methods',
    'estimated_cost' => 'Estimated cost',
];
