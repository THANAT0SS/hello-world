<?php
/**
 * Send Reminders - Run via cron every hour
 * Cron: 0 * * * * /usr/bin/php /path/to/send_reminders.php
 */

require_once 'config.php';
require_once 'lib/helpers.php';

logMessage('Reminder cron job started');

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db->connect_error) {
    logMessage('Database connection error: ' . $db->connect_error, 'error');
    die();
}

// Get reminders that should be sent in the next hour
$now = date('Y-m-d H:i:s');
$result = $db->query("
    SELECT * FROM reminders 
    WHERE sent = FALSE 
    AND reminder_time <= DATE_ADD(NOW(), INTERVAL 1 HOUR)
    AND reminder_time >= NOW()
");

while ($reminder = $result->fetch_assoc()) {
    $phone = $reminder['phone'];
    $bookingDate = $reminder['booking_date'];
    $bookingTime = $reminder['booking_time'];
    
    $message = "🔔 Reminder!\n\nYour appointment is tomorrow at " . date('g:i A', strtotime($bookingTime)) . ".\n\nSee you soon! 😊";
    
    sendWhatsAppReminder($phone, $message);
    
    // Mark as sent
    $db->query("UPDATE reminders SET sent = TRUE WHERE id = {$reminder['id']}");
    
    logMessage("Reminder sent to $phone");
}

$db->close();
logMessage('Reminder cron job completed');

/**
 * Send WhatsApp reminder
 */
function sendWhatsAppReminder($to, $message) {
    $url = 'https://graph.facebook.com/v18.0/' . PHONE_NUMBER_ID . '/messages';

    $payload = json_encode([
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'text',
        'text' => ['body' => $message]
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . WHATSAPP_TOKEN,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    curl_exec($ch);
    curl_close($ch);
}

?>