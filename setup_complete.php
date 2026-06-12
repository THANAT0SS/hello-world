<?php
/**
 * Complete Setup Script - Initialize Everything
 * Run once: php setup.sh
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  WhatsApp Salon Booking Bot - Complete Setup               ║\n";
echo "║  Version: 2.0 - Production Ready                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ========== CREATE DIRECTORIES ==========
echo "📁 Creating directories...\n";
$dirs = ['logs', 'cache', 'uploads', 'backups'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "   ✓ Created $dir/\n";
    }
}

// ========== DATABASE SETUP ==========
echo "\n📊 Setting up database...\n";

$db = new mysqli('localhost', 'root', '');
if ($db->connect_error) {
    echo "   ✗ MySQL connection failed: " . $db->connect_error . "\n";
    echo "   Please ensure MySQL is running.\n";
    exit(1);
}

$database = 'salon_booking';
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($db->query($sql) === TRUE) {
    echo "   ✓ Database '$database' created/exists\n";
} else {
    echo "   ✗ Error: " . $db->error . "\n";
    exit(1);
}

$db->select_db($database);

// ========== CREATE TABLES ==========
echo "\n📋 Creating tables...\n";

$tables = [
    'salon_config' => "
        CREATE TABLE IF NOT EXISTS salon_config (
            salon_id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            email VARCHAR(255) NOT NULL,
            address VARCHAR(255),
            website VARCHAR(255),
            description TEXT,
            
            monday_start TIME DEFAULT '10:00',
            monday_end TIME DEFAULT '18:00',
            tuesday_start TIME DEFAULT '10:00',
            tuesday_end TIME DEFAULT '18:00',
            wednesday_start TIME DEFAULT '10:00',
            wednesday_end TIME DEFAULT '18:00',
            thursday_start TIME DEFAULT '10:00',
            thursday_end TIME DEFAULT '18:00',
            friday_start TIME DEFAULT '10:00',
            friday_end TIME DEFAULT '18:00',
            saturday_start TIME DEFAULT '10:00',
            saturday_end TIME DEFAULT '16:00',
            sunday_start TIME DEFAULT NULL,
            sunday_end TIME DEFAULT NULL,
            
            timezone VARCHAR(50) DEFAULT 'Europe/Paris',
            reminder_hours INT DEFAULT 24,
            booking_confirmation_email BOOLEAN DEFAULT TRUE,
            booking_confirmation_whatsapp BOOLEAN DEFAULT TRUE,
            language VARCHAR(10) DEFAULT 'en',
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ",
    
    'services' => "
        CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            salon_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            duration INT NOT NULL COMMENT 'Duration in minutes',
            category VARCHAR(50),
            icon VARCHAR(10),
            active BOOLEAN DEFAULT TRUE,
            sort_order INT DEFAULT 0,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (salon_id) REFERENCES salon_config(salon_id) ON DELETE CASCADE,
            INDEX idx_salon_active (salon_id, active)
        )
    ",
    
    'customers' => "
        CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            salon_id INT NOT NULL,
            phone VARCHAR(20) NOT NULL,
            name VARCHAR(255),
            email VARCHAR(255),
            preferences TEXT COMMENT 'JSON format',
            total_bookings INT DEFAULT 0,
            total_spent DECIMAL(10, 2) DEFAULT 0,
            last_booking_date DATE,
            verified BOOLEAN DEFAULT FALSE,
            blocked BOOLEAN DEFAULT FALSE,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (salon_id) REFERENCES salon_config(salon_id) ON DELETE CASCADE,
            INDEX idx_salon_phone (salon_id, phone),
            UNIQUE KEY unique_phone_salon (salon_id, phone)
        )
    ",
    
    'bookings' => "
        CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            salon_id INT NOT NULL,
            customer_id INT,
            phone VARCHAR(20) NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            service_id INT NOT NULL,
            date DATE NOT NULL,
            time TIME NOT NULL,
            duration INT,
            
            status VARCHAR(50) DEFAULT 'confirmed' COMMENT 'confirmed, completed, cancelled, no-show',
            notes TEXT,
            cancellation_reason VARCHAR(255),
            rating INT COMMENT '1-5 stars',
            feedback TEXT,
            
            event_id VARCHAR(255) COMMENT 'Google Calendar event ID',
            confirmation_sent BOOLEAN DEFAULT FALSE,
            reminder_sent BOOLEAN DEFAULT FALSE,
            completion_confirmed BOOLEAN DEFAULT FALSE,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            
            FOREIGN KEY (salon_id) REFERENCES salon_config(salon_id) ON DELETE CASCADE,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (service_id) REFERENCES services(id),
            INDEX idx_date_salon (date, salon_id),
            INDEX idx_status (status),
            INDEX idx_customer (customer_id)
        )
    ",
    
    'staff' => "
        CREATE TABLE IF NOT EXISTS staff (
            id INT AUTO_INCREMENT PRIMARY KEY,
            salon_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            email VARCHAR(255),
            specialties TEXT COMMENT 'Comma-separated service IDs',
            availability TEXT COMMENT 'JSON format',
            active BOOLEAN DEFAULT TRUE,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (salon_id) REFERENCES salon_config(salon_id) ON DELETE CASCADE
        )
    ",
    
    'reminders' => "
        CREATE TABLE IF NOT EXISTS reminders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            salon_id INT NOT NULL,
            phone VARCHAR(20),
            email VARCHAR(255),
            reminder_type VARCHAR(50) DEFAULT 'whatsapp' COMMENT 'whatsapp, email, both',
            reminder_time DATETIME NOT NULL,
            scheduled_for INT COMMENT 'Hours before appointment',
            
            sent BOOLEAN DEFAULT FALSE,
            sent_at TIMESTAMP NULL,
            delivery_status VARCHAR(50),
            error_message TEXT,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
            FOREIGN KEY (salon_id) REFERENCES salon_config(salon_id) ON DELETE CASCADE,
            INDEX idx_sent_time (sent, reminder_time)
        )
    ",
    
    'messages' => "
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            salon_id INT NOT NULL,
            customer_id INT,
            phone VARCHAR(20),
            text TEXT NOT NULL,
            type VARCHAR(20) COMMENT 'incoming, outgoing',
            message_type VARCHAR(50) COMMENT 'booking, inquiry, feedback',
            booking_id INT,
            status VARCHAR(50) DEFAULT 'received',
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (salon_id) REFERENCES salon_config(salon_id) ON DELETE CASCADE,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (booking_id) REFERENCES bookings(id),
            INDEX idx_salon_date (salon_id, created_at),
            INDEX idx_type (type)
        )
    ",
    
    'ai_conversations' => "
        CREATE TABLE IF NOT EXISTS ai_conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            salon_id INT NOT NULL,
            customer_id INT,
            phone VARCHAR(20),
            conversation_history JSON,
            context VARCHAR(50) COMMENT 'booking, inquiry, feedback',
            ai_model VARCHAR(100),
            response_time INT COMMENT 'milliseconds',
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (salon_id) REFERENCES salon_config(salon_id) ON DELETE CASCADE,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            INDEX idx_phone (phone),
            INDEX idx_recent (updated_at)
        )
    ",
    
    'analytics' => "
        CREATE TABLE IF NOT EXISTS analytics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            salon_id INT NOT NULL,
            date DATE,
            total_messages INT DEFAULT 0,
            total_bookings INT DEFAULT 0,
            confirmed_bookings INT DEFAULT 0,
            completed_bookings INT DEFAULT 0,
            cancelled_bookings INT DEFAULT 0,
            total_revenue DECIMAL(10, 2) DEFAULT 0,
            avg_response_time INT,
            ai_accuracy_score DECIMAL(5, 2),
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (salon_id) REFERENCES salon_config(salon_id) ON DELETE CASCADE,
            INDEX idx_salon_date (salon_id, date)
        )
    ",
    
    'settings' => "
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            salon_id INT NOT NULL,
            setting_key VARCHAR(100) NOT NULL,
            setting_value TEXT,
            description TEXT,
            data_type VARCHAR(50),
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (salon_id) REFERENCES salon_config(salon_id) ON DELETE CASCADE,
            UNIQUE KEY unique_salon_setting (salon_id, setting_key)
        )
    "
];

foreach ($tables as $name => $sql) {
    if ($db->query($sql) === TRUE) {
        echo "   ✓ Table '$name' created\n";
    } else {
        echo "   ✗ Error creating '$name': " . $db->error . "\n";
    }
}

// ========== INSERT SAMPLE DATA ==========
echo "\n🌱 Inserting sample salon data...\n";

$sampleSalon = "
    INSERT IGNORE INTO salon_config 
    (name, phone, email, address, website, description, timezone)
    VALUES (
        'Your Salon Name',
        '+1234567890',
        'bookings@yoursalon.com',
        '123 Main Street, Your City',
        'https://yoursalon.com',
        'Welcome to our salon! We offer premium hair and beauty services.',
        'Europe/Paris'
    )
";

if ($db->query($sampleSalon) === TRUE) {
    $salonId = $db->insert_id ?: 1;
    echo "   ✓ Sample salon created (ID: $salonId)\n";
    
    // Insert sample services
    $services = [
        ['Haircut', 'Professional haircut with styling', 25, 60],
        ['Hair Coloring', 'Full color treatment with premium products', 50, 90],
        ['Hair Styling', 'Professional styling and blowdry', 30, 45],
        ['Facial', 'Relaxing facial treatment', 35, 45],
        ['Massage', 'Swedish or deep tissue massage', 40, 60],
        ['Manicure', 'Nail care and polish', 20, 30],
        ['Pedicure', 'Foot care and nail polish', 25, 45]
    ];
    
    foreach ($services as $service) {
        $db->query(
            "INSERT INTO services (salon_id, name, description, price, duration) 
             VALUES ($salonId, '{$service[0]}', '{$service[1]}', {$service[2]}, {$service[3]})"
        );
    }
    echo "   ✓ Sample services created\n";
} else {
    echo "   ! Sample data insertion note: " . $db->error . "\n";
}

$db->close();

// ========== CREATE CONFIG FILE ==========
echo "\n⚙️  Creating configuration file...\n";

if (!file_exists('config.php')) {
    copy('config.example.php', 'config.php');
    echo "   ✓ config.php created from template\n";
    echo "   ⚠️  Please edit config.php with your credentials!\n";
} else {
    echo "   ✓ config.php already exists\n";
}

// ========== CREATE .ENV FILE ==========
echo "\n🔐 Creating environment file...\n";

if (!file_exists('.env')) {
    $env = "
APP_ENV=local
APP_DEBUG=true

DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=salon_booking

WHATSAPP_VERIFY_TOKEN=test_verify_token_123
WHATSAPP_TOKEN=your_whatsapp_token
WHATSAPP_PHONE_ID=your_phone_number_id

GOOGLE_CALENDAR_ID=your-email@gmail.com
GOOGLE_SERVICE_ACCOUNT_PATH=google-service-account-key.json

OLLAMA_URL=http://localhost:11434/api/generate
OLLAMA_MODEL=neural-chat

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password

SALON_ID=1
TIMEZONE=Europe/Paris
LANGUAGE=en
";
    file_put_contents('.env', $env);
    echo "   ✓ .env file created\n";
    echo "   ⚠️  Please fill in .env with your credentials!\n";
}

// ========== CREATE LOG FILES ==========
echo "\n📝 Creating log files...\n";

$logFiles = ['logs/bot.log', 'logs/errors.log', 'logs/reminders.log', 'logs/ai.log'];
foreach ($logFiles as $file) {
    if (!file_exists($file)) {
        file_put_contents($file, "");
        echo "   ✓ Created $file\n";
    }
}

// ========== PERMISSIONS ==========
echo "\n🔓 Setting permissions...\n";

@chmod('logs', 0777);
@chmod('cache', 0777);
@chmod('uploads', 0777);
echo "   ✓ Permissions set\n";

// ========== COMPLETION ==========
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                ✅ SETUP COMPLETE!                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "📋 Next steps:\n";
echo "   1. Edit config.php with your credentials\n";
echo "   2. Edit .env with your API keys\n";
echo "   3. Install Ollama: https://ollama.ai\n";
echo "   4. Run: composer install\n";
echo "   5. Start: php -S localhost:8000\n";
echo "   6. Visit: http://localhost:8000/admin.php\n\n";

echo "📞 Default Salon Setup:\n";
echo "   Name: Your Salon Name\n";
echo "   Phone: +1234567890\n";
echo "   Email: bookings@yoursalon.com\n";
echo "   Edit in admin panel\n\n";

echo "🎉 Ready to test! Start Ollama and your PHP server.\n\n";

?>