<?php
/**
 * Helper Functions
 */

/**
 * Detect language from text
 */
function detectLanguage($text) {
    $text = strtolower($text);
    
    // French patterns
    if (preg_match('/(bonjour|je veux|réserver|quelle|merci|oui|non)/i', $text)) return 'fr';
    
    // Spanish patterns
    if (preg_match('/(hola|quiero|reservar|cuándo|gracias|sí|no)/i', $text)) return 'es';
    
    // German patterns
    if (preg_match('/(hallo|ich möchte|buchen|wann|danke|ja|nein)/i', $text)) return 'de';
    
    return 'en';
}

/**
 * Translate text
 */
function translate($text, $language) {
    $translations = [
        'That time is not available' => [
            'en' => 'That time is not available',
            'fr' => 'Ce créneau n\'est pas disponible',
            'es' => 'Ese horario no está disponible',
            'de' => 'Dieser Zeitpunkt ist nicht verfügbar'
        ],
        'Available times' => [
            'en' => 'Available times',
            'fr' => 'Créneaux disponibles',
            'es' => 'Horarios disponibles',
            'de' => 'Verfügbare Zeiten'
        ],
        'What is your name?' => [
            'en' => 'What is your name?',
            'fr' => 'Quel est votre nom?',
            'es' => '¿Cuál es tu nombre?',
            'de' => 'Wie heißt du?'
        ]
    ];
    
    return $translations[$text][$language] ?? $text;
}

/**
 * Parse date from text
 */
function parseDate($text, $language = 'en') {
    $text = strtolower($text);
    $now = new DateTime();

    // Tomorrow
    if (preg_match('/(tomorrow|demain|mañana|morgen)/i', $text)) {
        return (clone $now)->modify('+1 day')->format('Y-m-d');
    }

    // Day names (English)
    $en_days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    foreach ($en_days as $day) {
        if (strpos($text, $day) !== false) {
            return (clone $now)->modify('next ' . ucfirst($day))->format('Y-m-d');
        }
    }

    // Day names (French)
    $fr_days = ['lundi' => 'monday', 'mardi' => 'tuesday', 'mercredi' => 'wednesday', 
                'jeudi' => 'thursday', 'vendredi' => 'friday', 'samedi' => 'saturday'];
    foreach ($fr_days as $fr, $en) {
        if (strpos($text, $fr) !== false) {
            return (clone $now)->modify('next ' . $en)->format('Y-m-d');
        }
    }

    // Spanish days
    $es_days = ['lunes' => 'monday', 'martes' => 'tuesday', 'miércoles' => 'wednesday',
                'jueves' => 'thursday', 'viernes' => 'friday', 'sábado' => 'saturday'];
    foreach ($es_days as $es, $en) {
        if (strpos($text, $es) !== false) {
            return (clone $now)->modify('next ' . $en)->format('Y-m-d');
        }
    }

    // German days
    $de_days = ['montag' => 'monday', 'dienstag' => 'tuesday', 'mittwoch' => 'wednesday',
                'donnerstag' => 'thursday', 'freitag' => 'friday', 'samstag' => 'saturday'];
    foreach ($de_days as $de, $en) {
        if (strpos($text, $de) !== false) {
            return (clone $now)->modify('next ' . $en)->format('Y-m-d');
        }
    }

    // Specific dates (d/m or d-m)
    if (preg_match('/(\d{1,2})[-\/](\d{1,2})/', $text, $m)) {
        try {
            $date = DateTime::createFromFormat('d-m', $m[1] . '-' . $m[2]);
            if ($date) return $date->format('Y-m-d');
        } catch (Exception $e) {}
    }

    return null;
}

/**
 * Parse time from text
 */
function parseTime($text) {
    if (preg_match('/(\d{1,2}):?(\d{2})?\s*(am|pm|h)?/i', $text, $matches)) {
        $hour = (int)$matches[1];
        $min = $matches[2] ?? '00';
        $period = strtolower($matches[3] ?? '');

        if (in_array($period, ['pm', 'p'])) {
            if ($hour !== 12) $hour += 12;
        } elseif (in_array($period, ['am', 'a'])) {
            if ($hour === 12) $hour = 0;
        }

        return str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . $min;
    }

    return null;
}

/**
 * Log messages
 */
function logMessage($message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = defined('LOG_FILE') ? LOG_FILE : 'logs/bot.log';
    
    @mkdir('logs', 0777, true);
    
    $logEntry = "[$timestamp] [$type] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo $logEntry;
    }
}

?>