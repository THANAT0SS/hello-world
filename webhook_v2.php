<?php
/**
 * Complete Webhook - Production Ready
 * Handles all WhatsApp messages with real data validation
 */

require_once 'config.php';
require_once 'vendor/autoload.php';
require_once 'lib/WhatsAppHandler.php';
require_once 'lib/BookingEngine.php';
require_once 'lib/NotificationManager.php';
require_once 'lib/AIProcessor.php';

use App\WhatsAppHandler;
use App\BookingEngine;
use App\NotificationManager;
use App\AIProcessor;

// ========== INITIALIZE ==========
$db = getDBConnection();
$startTime = microtime(true);

logMessage('=== Webhook Request Started ===' );

try {
    // ========== VERIFY WEBHOOK ==========
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $token = $_GET['hub_verify_token'] ?? '';
        $challenge = $_GET['hub_challenge'] ?? '';
        
        if ($token === VERIFY_TOKEN) {
            logMessage('Webhook verified successfully');
            echo $challenge;
            exit(0);
        }
        
        http_response_code(403);
        logMessage('Webhook verification failed - invalid token', 'error');
        die('Invalid token');
    }
    
    // ========== GET REQUEST BODY ==========
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['entry'][0]['changes'][0]['value']['messages'][0])) {
        logMessage('Invalid webhook payload');
        http_response_code(200);
        exit(0);
    }
    
    $message = $input['entry'][0]['changes'][0]['value']['messages'][0];
    $from = $message['from'];
    $userMessage = trim($message['text']['body'] ?? '');
    $messageId = $message['id'] ?? null;
    
    logMessage("Message received from $from: " . substr($userMessage, 0, 50));
    
    // ========== INITIALIZE HANDLERS ==========
    $whatsappHandler = new WhatsAppHandler($db, SALON_ID);
    $bookingEngine = new BookingEngine($db, SALON_ID);
    $notificationManager = new NotificationManager($db, SALON_ID);
    $aiProcessor = new AIProcessor($db, SALON_ID);
    
    // ========== CHECK RATE LIMITING ==========
    if (RATE_LIMIT_ENABLED && !$whatsappHandler->checkRateLimit($from)) {
        logMessage("Rate limit exceeded for $from", 'warn');
        $whatsappHandler->sendMessage(
            $from,
            "⏳ Please wait a moment before sending another message.\nWe're processing your request."
        );
        http_response_code(200);
        exit(0);
    }
    
    // ========== GET SALON DATA ==========
    $salonData = $whatsappHandler->getSalonData();
    if (!$salonData) {
        logMessage('Salon data not found', 'error');
        http_response_code(500);
        exit(1);
    }
    
    // ========== GET SERVICES ==========
    $services = $whatsappHandler->getServices();
    logMessage('Loaded ' . count($services) . ' services');
    
    // ========== DETECT LANGUAGE ==========
    $language = $aiProcessor->detectLanguage($userMessage);
    logMessage("Detected language: $language");
    
    // ========== SAVE INCOMING MESSAGE ==========
    $whatsappHandler->saveMessage($from, $userMessage, 'incoming', 'inquiry');
    
    // ========== PROCESS WITH AI ==========
    logMessage('Processing with AI...');
    $aiStartTime = microtime(true);
    
    $aiResponse = $aiProcessor->processMessage(
        $userMessage,
        $salonData,
        $services,
        $language
    );
    
    $aiResponseTime = (microtime(true) - $aiStartTime) * 1000; // milliseconds
    logMessage("AI response time: {$aiResponseTime}ms");
    
    // ========== EXTRACT BOOKING INFORMATION ==========
    $bookingData = $aiProcessor->extractBookingInfo(
        $userMessage,
        $services,
        $language
    );
    
    $finalResponse = $aiResponse;
    
    if ($bookingData) {
        logMessage('Booking data extracted: ' . json_encode($bookingData));
        
        // ========== VALIDATE BOOKING ==========
        $validation = $bookingEngine->validateBooking(
            $bookingData,
            $salonData,
            $services
        );
        
        if ($validation['valid']) {
            logMessage('Booking validated successfully');
            
            // ========== SAVE BOOKING ==========
            $bookingId = $bookingEngine->createBooking(
                $from,
                $bookingData,
                $salonData,
                $language
            );
            
            if ($bookingId) {
                logMessage("Booking created with ID: $bookingId");
                
                // ========== BUILD CONFIRMATION MESSAGE ==========
                $finalResponse = $bookingEngine->buildConfirmationMessage(
                    $bookingId,
                    $salonData,
                    $language
                );
                
                // ========== SEND CONFIRMATIONS ==========
                // To customer
                $whatsappHandler->sendMessage($from, $finalResponse);
                
                // To salon owner (email)
                if (FEATURE_EMAIL_CONFIRMATION) {
                    $notificationManager->sendBookingConfirmationEmail(
                        $bookingId,
                        $salonData,
                        $bookingData,
                        $from
                    );
                }
                
                // To salon owner (WhatsApp)
                if (FEATURE_WHATSAPP_REMINDER) {
                    $notificationManager->sendSalonAlert(
                        $bookingId,
                        $salonData,
                        $bookingData,
                        $from
                    );
                }
                
                // ========== SYNC TO GOOGLE CALENDAR ==========
                if (FEATURE_GOOGLE_CALENDAR) {
                    $eventId = $bookingEngine->syncToGoogleCalendar(
                        $bookingId,
                        $salonData
                    );
                    if ($eventId) {
                        logMessage("Synced to Google Calendar: $eventId");
                    }
                }
                
                // ========== SCHEDULE REMINDER ==========
                $reminderId = $notificationManager->scheduleReminder(
                    $bookingId,
                    $from,
                    $bookingData,
                    $salonData,
                    $language
                );
                logMessage("Reminder scheduled: $reminderId");
                
                // ========== UPDATE ANALYTICS ==========
                $whatsappHandler->updateAnalytics('booking_confirmed');
                
            } else {
                logMessage('Failed to create booking', 'error');
                $finalResponse = "❌ " . $aiProcessor->translate(
                    'booking_error',
                    $language
                );
            }
        } else {
            logMessage('Booking validation failed: ' . $validation['error']);
            $finalResponse = "❌ " . $validation['error'] . "\n\n" .
                           $bookingEngine->getAvailableSlots(
                               $bookingData['date'] ?? date('Y-m-d'),
                               $salonData
                           );
        }
    } else {
        logMessage('No booking data extracted - sending AI response');
        // Send AI response as-is for inquiries
    }
    
    // ========== SEND RESPONSE TO CUSTOMER ==========
    $whatsappHandler->sendMessage($from, $finalResponse);
    
    // ========== SAVE OUTGOING MESSAGE ==========
    $whatsappHandler->saveMessage($from, $finalResponse, 'outgoing', 'response');
    
    // ========== LOG CONVERSATION ==========
    $aiProcessor->saveConversation($from, $userMessage, $finalResponse, $aiResponseTime);
    
    // ========== CALCULATE TOTAL TIME ==========
    $totalTime = (microtime(true) - $startTime) * 1000;
    logMessage("Request completed in {$totalTime}ms");
    
    // ========== UPDATE ANALYTICS ==========
    $whatsappHandler->updateAnalytics('message_received', (int)$totalTime);
    
    http_response_code(200);
    
} catch (Exception $e) {
    logMessage('Exception: ' . $e->getMessage(), 'error');
    logMessage('Stack trace: ' . $e->getTraceAsString(), 'error');
    http_response_code(500);
    exit(1);
} finally {
    // Always return 200 to WhatsApp
    if (http_response_code() !== 200) {
        http_response_code(200);
    }
}

?>
