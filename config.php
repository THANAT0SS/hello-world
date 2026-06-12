<?php
/**
 * Enhanced Configuration - All Settings in One Place
 * Copy this to config.php and fill in your details
 */

// ========== LOAD ENVIRONMENT VARIABLES ==========
if (file_exists('.env')) {
    $env = parse_ini_file('.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// ========== APPLICATION ==========
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? false);
define('APP_VERSION', '2.0.0');
define('SALON_ID', $_ENV['SALON_ID'] ?? 1);

// ========== DATABASE ==========
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'salon_booking');
define('DB_CHARSET', 'utf8mb4');

// ========== WHATSAPP CONFIG ==========
define('VERIFY_TOKEN', $_ENV['WHATSAPP_VERIFY_TOKEN'] ?? 'test_verify_token_123');
define('WHATSAPP_TOKEN', $_ENV['WHATSAPP_TOKEN'] ?? '');
define('PHONE_NUMBER_ID', $_ENV['WHATSAPP_PHONE_ID'] ?? '');
define('WHATSAPP_API_VERSION', 'v18.0');
define('WHATSAPP_TIMEOUT', 10);

// ========== GOOGLE CALENDAR ==========
define('GOOGLE_CALENDAR_ID', $_ENV['GOOGLE_CALENDAR_ID'] ?? '');
define('GOOGLE_SERVICE_ACCOUNT_PATH', $_ENV['GOOGLE_SERVICE_ACCOUNT_PATH'] ?? 'google-service-account-key.json');

// ========== OLLAMA AI ==========
define('OLLAMA_URL', $_ENV['OLLAMA_URL'] ?? 'http://localhost:11434/api/generate');
define('OLLAMA_MODEL', $_ENV['OLLAMA_MODEL'] ?? 'neural-chat');
define('OLLAMA_TIMEOUT', 30);
define('OLLAMA_TEMPERATURE', 0.7);
define('AI_MAX_RESPONSE_TIME', 5000); // milliseconds

// ========== EMAIL/SMTP ==========
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('SENDER_EMAIL', $_ENV['SENDER_EMAIL'] ?? 'bookings@yoursalon.com');
define('SENDER_NAME', $_ENV['SENDER_NAME'] ?? 'Your Salon');

// ========== LOCALIZATION ==========
define('TIMEZONE', $_ENV['TIMEZONE'] ?? 'Europe/Paris');
define('LOCALE', $_ENV['LANGUAGE'] ?? 'en');
define('SUPPORTED_LANGUAGES', ['en', 'fr', 'es', 'de']);

// ========== LOGGING ==========
define('LOG_DIR', 'logs');
define('LOG_BOT', LOG_DIR . '/bot.log');
define('LOG_ERROR', LOG_DIR . '/errors.log');
define('LOG_REMINDER', LOG_DIR . '/reminders.log');
define('LOG_AI', LOG_DIR . '/ai.log');
define('LOG_MAX_SIZE', 10485760); // 10MB

// ========== CACHE ==========
define('CACHE_DIR', 'cache');
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // 1 hour

// ========== FEATURES ==========
define('FEATURE_EMAIL_CONFIRMATION', true);
define('FEATURE_WHATSAPP_REMINDER', true);
define('FEATURE_GOOGLE_CALENDAR', true);
define('FEATURE_CUSTOMER_FEEDBACK', true);
define('FEATURE_MULTILINGUAL', true);
define('FEATURE_ANALYTICS', true);
define('FEATURE_STAFF_ASSIGNMENT', false); // Coming soon
define('FEATURE_PAYMENT_INTEGRATION', false); // Coming soon

// ========== BUSINESS RULES ==========
define('MIN_BOOKING_ADVANCE_HOURS', 2); // Minimum hours in advance
define('MAX_BOOKING_ADVANCE_DAYS', 90); // Maximum days in advance
define('BOOKING_CONFIRMATION_TIMEOUT', 300); // 5 minutes to confirm
define('REMINDER_HOURS_BEFORE', 24); // Send reminder 24 hours before
define('ALLOW_SAME_DAY_BOOKING', true);
define('REQUIRE_CUSTOMER_EMAIL', false);
define('REQUIRE_CUSTOMER_NAME', true);

// ========== RATE LIMITING ==========
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_MESSAGES_PER_MINUTE', 5);
define('RATE_LIMIT_BOOKINGS_PER_HOUR', 10);

// ========== SECURITY ==========
define('ENABLE_SIGNATURE_VERIFICATION', true);
define('ENABLE_CORS', false);
define('MAX_REQUEST_SIZE', 1048576); // 1MB
define('ALLOWED_IPS', []); // Empty = allow all

// ========== PATHS ==========
define('ROOT_DIR', __DIR__);
define('LIB_DIR', ROOT_DIR . '/lib');
define('VENDOR_DIR', ROOT_DIR . '/vendor');
define('UPLOADS_DIR', ROOT_DIR . '/uploads');
define('BACKUPS_DIR', ROOT_DIR . '/backups');

// ========== DATABASE CONNECTION HELPER ==========
function getDBConnection() {
    static $db = null;
    
    if ($db === null) {
        $db = new mysqli(
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME
        );
        
        if ($db->connect_error) {
            logError('Database Connection Failed: ' . $db->connect_error);
            http_response_code(500);
            die('Database error');
        }
        
        $db->set_charset(DB_CHARSET);
        date_default_timezone_set(TIMEZONE);
    }
    
    return $db;
}

// ========== ERROR HANDLING ==========
function logError($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] ERROR: $message\n";
    @file_put_contents(LOG_ERROR, $logEntry, FILE_APPEND);
    
    if (APP_DEBUG) {
        echo $logEntry;
    }
}

function logMessage($message, $level = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [" . strtoupper($level) . "] $message\n";
    @file_put_contents(LOG_BOT, $logEntry, FILE_APPEND);
    
    if (APP_DEBUG && $level === 'error') {
        echo $logEntry;
    }
}

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    
    $message = "$errstr in $errfile:$errline";
    logError($message);
    
    if (APP_DEBUG) {
        return false; // Let PHP handle it
    }
    return true; // Suppress error
});

// Create necessary directories
foreach ([LOG_DIR, CACHE_DIR, UPLOADS_DIR, BACKUPS_DIR] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

?>
