<?php
/**
 * Database Setup Script
 * Run this ONCE: php setup_database.php
 */

echo "🔧 Setting up database...\n\n";

// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'salon_booking';

// Create connection to MySQL server (without selecting database)
$conn = new mysqli($host, $user, $password);

if ($conn->connect_error) {
    die('❌ Connection failed: ' . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === TRUE) {
    echo "✅ Database '$database' created/exists\n";
} else {
    echo "❌ Error creating database: " . $conn->error;
    die();
}

// Select the database
$conn->select_db($database);

// Create customers table
$sql = "CREATE TABLE IF NOT EXISTS customers (
    phone VARCHAR(20) PRIMARY KEY,
    name VARCHAR(255),
    status VARCHAR(50) DEFAULT 'idle',
    selected_service VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Customers table created\n";
} else {
    echo "❌ Error: " . $conn->error;
}

// Create bookings table
$sql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20),
    name VARCHAR(255),
    service VARCHAR(100),
    date DATE,
    time TIME,
    status VARCHAR(50) DEFAULT 'confirmed',
    event_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (phone) REFERENCES customers(phone) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Bookings table created\n";
} else {
    echo "❌ Error: " . $conn->error;
}

// Create messages table
$sql = "CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20),
    text TEXT,
    type VARCHAR(20),
    timestamp INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Messages table created\n";
} else {
    echo "❌ Error: " . $conn->error;
}

// Create reminders table
$sql = "CREATE TABLE IF NOT EXISTS reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20),
    booking_date DATE,
    booking_time TIME,
    reminder_time DATETIME,
    sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (phone) REFERENCES customers(phone) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Reminders table created\n";
} else {
    echo "❌ Error: " . $conn->error;
}

$conn->close();

echo "\n✅ DATABASE SETUP COMPLETE!\n";
echo "Next steps:\n";
echo "1. Copy config.example.php to config.php\n";
echo "2. Fill in your credentials\n";
echo "3. Start Ollama: ollama serve\n";
echo "4. Run: php -S localhost:8000\n";
echo "5. Visit: http://localhost:8000/admin.php\n";

?>