<?php
/**
 * Configuration File
 * Copy this to config.php and fill in your credentials
 */

// ========== WHATSAPP CONFIG ==========
define('VERIFY_TOKEN', 'your_verify_token_123');
define('WHATSAPP_TOKEN', 'your_whatsapp_business_account_token');
define('PHONE_NUMBER_ID', 'your_phone_number_id');
define('BUSINESS_ACCOUNT_ID', 'your_business_account_id');

// ========== GOOGLE CALENDAR CONFIG ==========
define('GOOGLE_CALENDAR_ID', 'your-salon@gmail.com');
define('GOOGLE_SERVICE_ACCOUNT_PATH', 'path/to/google-service-account-key.json');

// ========== OLLAMA AI CONFIG ==========
define('OLLAMA_URL', 'http://localhost:11434/api/generate');
define('OLLAMA_MODEL', 'neural-chat'); // or 'mistral', 'llama2'

// ========== DATABASE CONFIG ==========
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'salon_booking');

// ========== SALON CONFIG ==========
$SALON_CONFIG = [
    'name' => 'Your Salon Name',
    'timezone' => 'Europe/Paris',
    'owner_phone' => '+1234567890', // For SMS reminders
    'owner_email' => 'owner@salon.com',
    
    'services' => [
        'haircut' => ['name' => 'Haircut', 'price' => 25, 'duration' => 60],
        'coloring' => ['name' => 'Coloring', 'price' => 50, 'duration' => 90],
        'massage' => ['name' => 'Massage', 'price' => 40, 'duration' => 60],
        'facial' => ['name' => 'Facial', 'price' => 35, 'duration' => 45],
    ],
    
    'work_hours' => [
        'start' => '10:00',
        'end' => '18:00',
    ],
    
    'work_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
    
    'languages' => ['en', 'fr', 'es', 'de'],
];

// ========== LOGGING ==========
define('LOG_FILE', 'logs/bot.log');
define('DEBUG_MODE', true);

?>