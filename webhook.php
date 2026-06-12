<?php
/**
 * Main Webhook - Receives WhatsApp messages
 * URL: https://yourdomain.com/webhook.php
 */

require_once 'lib/helpers.php';
require_once 'config.php';

// Load Google Calendar API
require_once 'vendor/autoload.php';
use Google\Client;
use Google\Service\Calendar;

logMessage('Webhook request received');

// ========== WEBHOOK VERIFICATION ==========
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';
    
    if ($token === VERIFY_TOKEN) {
        logMessage('Webhook verified successfully');
        echo $challenge;
        exit;
    }
    
    http_response_code(403);
    logMessage('Invalid verify token: ' . $token, 'error');
    die('Invalid token');
}

// ========== RECEIVE MESSAGE ==========
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['entry'][0]['changes'][0]['value']['messages'][0])) {
    logMessage('No message in payload');
    http_response_code(200);
    exit;
}

$message = $input['entry'][0]['changes'][0]['value']['messages'][0];
$from = $message['from'];
$userMessage = trim($message['text']['body'] ?? '');

logMessage("Message from $from: $userMessage");

// ========== GET DATABASE CONNECTION ==========
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    logMessage('Database connection error: ' . $db->connect_error, 'error');
    exit;
}

// ========== DETECT LANGUAGE ==========
$language = detectLanguage($userMessage);
logMessage("Detected language: $language");

// ========== GET AI RESPONSE ==========
$aiResponse = getLocalAIResponse($userMessage, $SALON_CONFIG);
logMessage("AI Response: $aiResponse");

// ========== EXTRACT BOOKING INFO ==========
$bookingData = extractBookingInfo($userMessage, $aiResponse);

if ($bookingData) {
    logMessage('Booking data extracted: ' . json_encode($bookingData));
    
    // Check availability
    $available = checkAvailability($bookingData['date'], $bookingData['time'], $db, $SALON_CONFIG);
    
    if ($available) {
        logMessage('Time slot available');
        
        // Add to Google Calendar
        $eventId = addToGoogleCalendar(
            $bookingData['date'],
            $bookingData['time'],
            $bookingData['service'],
            $from,
            $bookingData['name'],
            $SALON_CONFIG
        );
        
        // Save to database
        $db->query("INSERT INTO bookings (phone, name, service, date, time, event_id) 
                    VALUES ('$from', '{$bookingData['name']}', '{$bookingData['service']}', 
                    '{$bookingData['date']}', '{$bookingData['time']}', '$eventId')");
        
        // Schedule reminder
        scheduleReminder($from, $bookingData['date'], $bookingData['time'], $db);
        
        $response = formatConfirmation($bookingData, $SALON_CONFIG, $language);
    } else {
        logMessage('Time slot not available');
        $response = "❌ " . translate('That time is not available', $language) . 
                    "\n\n" . getAvailableSlots($bookingData['date'], $SALON_CONFIG, $language);
    }
} else {
    logMessage('No booking data extracted');
    $response = $aiResponse;
}

// Save incoming message to database
$db->query("INSERT INTO messages (phone, text, type, timestamp) VALUES ('$from', '$userMessage', 'incoming', " . time() . ")");

// Send response
sendWhatsAppMessage($from, $response);

// Save outgoing message
$db->query("INSERT INTO messages (phone, text, type, timestamp) VALUES ('$from', '$response', 'outgoing', " . time() . ")");

$db->close();
http_response_code(200);

/**
 * ========== LOCAL AI RESPONSE (OLLAMA) ==========
 */
function getLocalAIResponse($userMessage, $salonConfig) {
    $services = implode(', ', array_column($salonConfig['services'], 'name'));
    
    $prompt = "You are a friendly salon booking assistant.
Available services: $services
Work hours: " . $salonConfig['work_hours']['start'] . "-" . $salonConfig['work_hours']['end'] . " daily
Current time: " . date('Y-m-d H:i') . "

Customer: $userMessage

You MUST:
1. Extract: date, time, service, and name if mentioned
2. If booking: confirm with details
3. If asking about availability: tell them open Mon-Sat 10am-6pm
4. If asking about prices: give price list
5. Keep response under 100 words
6. Be friendly and warm

Respond naturally like a human assistant on WhatsApp.";

    $payload = json_encode([
        'model' => OLLAMA_MODEL,
        'prompt' => $prompt,
        'stream' => false,
        'temperature' => 0.7,
    ]);

    $ch = curl_init(OLLAMA_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        $aiText = trim($data['response'] ?? '');
        return $aiText;
    }

    logMessage("Ollama Error: HTTP $httpCode", 'error');
    return "Hi! 👋 How can I help you book an appointment today?";
}

/**
 * ========== EXTRACT BOOKING INFO ==========
 */
function extractBookingInfo($userMessage, $aiResponse) {
    $combined = strtolower($userMessage . ' ' . $aiResponse);

    $date = parseDate($userMessage);
    if (!$date) return null;

    $time = parseTime($userMessage);
    if (!$time) return null;

    $services = ['haircut', 'coloring', 'massage', 'facial', 'trim', 'cut', 'color'];
    $service = 'Haircut';
    foreach ($services as $svc) {
        if (strpos($combined, $svc) !== false) {
            $service = ucfirst($svc);
            break;
        }
    }

    $name = 'Guest';
    if (preg_match('/(?:my name is|i\'?m|name\'?s|call me)\s+(\w+)/i', $userMessage, $matches)) {
        $name = ucfirst($matches[1]);
    }

    return [
        'date' => $date,
        'time' => $time,
        'service' => $service,
        'name' => $name
    ];
}

/**
 * ========== CHECK AVAILABILITY ==========
 */
function checkAvailability($date, $time, $db, $salonConfig) {
    // Check database for conflicts
    $result = $db->query("SELECT id FROM bookings WHERE date = '$date' AND time = '$time'");
    return $result->num_rows === 0;
}

/**
 * ========== GET AVAILABLE SLOTS ==========
 */
function getAvailableSlots($date, $salonConfig, $language) {
    $slots = [
        'en' => "Available times on $date:\n",
        'fr' => "Créneaux disponibles le $date:\n",
        'es' => "Horarios disponibles el $date:\n",
        'de' => "Verfügbare Zeiten am $date:\n"
    ];

    $slotTexts = [];
    $start = strtotime($salonConfig['work_hours']['start']);
    $end = strtotime($salonConfig['work_hours']['end']);
    
    for ($i = $start; $i < $end; $i += 3600) {
        $slotTexts[] = date('g:ia', $i);
    }

    return ($slots[$language] ?? $slots['en']) . implode(', ', $slotTexts);
}

/**
 * ========== FORMAT CONFIRMATION ==========
 */
function formatConfirmation($bookingData, $salonConfig, $language) {
    $service = null;
    foreach ($salonConfig['services'] as $key => $svc) {
        if (strtolower($svc['name']) === strtolower($bookingData['service'])) {
            $service = $svc;
            break;
        }
    }

    $confirmations = [
        'en' => "✅ Booking Confirmed!\n\n📝 Name: {$bookingData['name']}\n📅 Date: {$bookingData['date']}\n⏰ Time: {$bookingData['time']}\n💇 Service: {$bookingData['service']}\n💶 Price: €{$service['price']}\n\n🔔 We'll send you a reminder 24 hours before.\nThanks for choosing us!",
        'fr' => "✅ Réservation confirmée!\n\n📝 Nom: {$bookingData['name']}\n📅 Date: {$bookingData['date']}\n⏰ Heure: {$bookingData['time']}\n💇 Service: {$bookingData['service']}\n💶 Prix: €{$service['price']}\n\n🔔 Nous vous enverrons un rappel 24 heures avant.\nMerci de nous avoir choisis!",
        'es' => "✅ ¡Reserva confirmada!\n\n📝 Nombre: {$bookingData['name']}\n📅 Fecha: {$bookingData['date']}\n⏰ Hora: {$bookingData['time']}\n💇 Servicio: {$bookingData['service']}\n💶 Precio: €{$service['price']}\n\n🔔 Te enviaremos un recordatorio 24 horas antes.\n¡Gracias por elegirnos!",
        'de' => "✅ Buchung bestätigt!\n\n📝 Name: {$bookingData['name']}\n📅 Datum: {$bookingData['date']}\n⏰ Zeit: {$bookingData['time']}\n💇 Service: {$bookingData['service']}\n💶 Preis: €{$service['price']}\n\n🔔 Wir senden dir 24 Stunden vorher eine Erinnerung.\nDanke, dass du uns gewählt hast!"
    ];

    return $confirmations[$language] ?? $confirmations['en'];
}

/**
 * ========== ADD TO GOOGLE CALENDAR ==========
 */
function addToGoogleCalendar($date, $time, $service, $phone, $name, $salonConfig) {
    try {
        if (!file_exists(GOOGLE_SERVICE_ACCOUNT_PATH)) {
            logMessage('Google service account file not found', 'error');
            return null;
        }

        $client = new Client();
        $client->setAuthConfig(GOOGLE_SERVICE_ACCOUNT_PATH);
        $client->addScope(Calendar::CALENDAR);

        $calendarService = new Calendar($client);

        $duration = 60;
        foreach ($salonConfig['services'] as $svc) {
            if (strtolower($svc['name']) === strtolower($service)) {
                $duration = $svc['duration'];
                break;
            }
        }

        $startTime = new DateTime($date . ' ' . $time);
        $endTime = (clone $startTime)->modify("+$duration minutes");

        $event = new \Google\Service\Calendar\Event([
            'summary' => "$service - $name",
            'description' => "Phone: $phone\nBooked via WhatsApp",
            'start' => [
                'dateTime' => $startTime->format('c'),
                'timeZone' => $salonConfig['timezone'],
            ],
            'end' => [
                'dateTime' => $endTime->format('c'),
                'timeZone' => $salonConfig['timezone'],
            ],
        ]);

        $createdEvent = $calendarService->events->insert(GOOGLE_CALENDAR_ID, $event);
        logMessage("Booking added to Google Calendar: " . $createdEvent->getId());
        return $createdEvent->getId();
    } catch (Exception $e) {
        logMessage("Google Calendar Error: " . $e->getMessage(), 'error');
        return null;
    }
}

/**
 * ========== SCHEDULE REMINDER ==========
 */
function scheduleReminder($phone, $date, $time, $db) {
    $reminderTime = date('Y-m-d H:i:s', strtotime("$date $time -24 hours"));
    
    $db->query("INSERT INTO reminders (phone, booking_date, booking_time, reminder_time) 
               VALUES ('$phone', '$date', '$time', '$reminderTime')");
    
    logMessage("Reminder scheduled for $phone");
}

/**
 * ========== SEND WHATSAPP MESSAGE ==========
 */
function sendWhatsAppMessage($to, $message) {
    $url = 'https://graph.facebook.com/v18.0/' . PHONE_NUMBER_ID . '/messages';

    $payload = json_encode([
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'text',
        'text' => ['body' => substr($message, 0, 4096)]
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

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        logMessage("Message sent to $to");
    } else {
        logMessage("Failed to send message to $to: HTTP $httpCode", 'error');
    }
}

?>