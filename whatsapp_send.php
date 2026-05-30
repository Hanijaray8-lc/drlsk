<?php
// whatsapp_send.php - Updated to use Render.com API (No local Node.js required)
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ==================== CONFIGURATION ====================
// REPLACE THIS WITH YOUR RENDER.COM URL AFTER DEPLOYMENT
// Example: https://whatsapp-bot.onrender.com
$RENDER_API_URL = 'https://drlsk.onrender.com';
// =======================================================

function sendResponse($data) {
    echo json_encode($data);
    exit;
}

// Check if bot is ready on Render
function isBotReady() {
    global $RENDER_API_URL;
    
    $ch = curl_init($RENDER_API_URL . '/status');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $status = json_decode($response, true);
        return isset($status['ready']) && $status['ready'] === true;
    }
    return false;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ==================== SEND BULK MESSAGES ====================
if ($action == 'send_bulk') {
    
    // Get data from POST request
    $patientsJson = $_POST['patients'] ?? '[]';
    $patients = json_decode($patientsJson, true);
    $customMessage = $_POST['custom_message'] ?? '';
    $campaignDate = $_POST['campaign_date'] ?? date('Y-m-d');
    $campaignTopic = $_POST['campaign_topic'] ?? 'Health Camp';
    $campaignStartTime = $_POST['campaign_start_time'] ?? '09:00';
    $campaignEndTime = $_POST['campaign_end_time'] ?? '13:00';
    
    // Validate patients
    if (!is_array($patients) || empty($patients)) {
        sendResponse(['queued' => 0, 'message' => 'No patients selected']);
    }
    
    // Check if Render bot is ready
    if (!isBotReady()) {
        sendResponse([
            'status' => 'not_ready',
            'message' => 'WhatsApp bot is not ready. Please ensure Render service is running and QR code is scanned.',
            'queued' => 0
        ]);
    }
    
    // Build messages for each patient
    $messagesToSend = [];
    foreach ($patients as $patient) {
        $phone = $patient['phone'] ?? '';
        $name = $patient['name'] ?? 'Patient';
        
        if (empty($phone)) continue;
        
        // Clean and validate phone number
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // Remove leading zero if present
        if (substr($cleanPhone, 0, 1) == '0') {
            $cleanPhone = substr($cleanPhone, 1);
        }
        
        // Add country code for Indian numbers (91)
        if (strlen($cleanPhone) == 10) {
            $cleanPhone = '91' . $cleanPhone;
        }
        
        // Skip invalid numbers
        if (strlen($cleanPhone) < 10 || strlen($cleanPhone) > 15) {
            continue;
        }
        
        // Format time for message (12-hour format)
        $startTimeFormatted = date('g:i A', strtotime($campaignStartTime));
        $endTimeFormatted = date('g:i A', strtotime($campaignEndTime));
        
        // Build the message
        $message = '';
        if (!empty($customMessage)) {
            $message .= $customMessage . "\n\n";
        }
        $message .= "Hello " . $name . ",\n\n";
        $message .= "📅 *Campaign Date:* " . $campaignDate . "\n";
        $message .= "📌 *Topic:* " . $campaignTopic . "\n";
        $message .= "🕒 *Time:* " . $startTimeFormatted . " - " . $endTimeFormatted . "\n\n";
        $message .= "📍 *Venue:* Dr. LSK Clinic\n\n";
        $message .= "*Please confirm your attendance.*\n\n";
        $message .= "_This is an automated message from Dr. LSK Clinic_";
        
        $messagesToSend[] = [
            'phone' => $cleanPhone,
            'name' => $name,
            'message' => $message
        ];
    }
    
    if (empty($messagesToSend)) {
        sendResponse(['queued' => 0, 'message' => 'No valid phone numbers found']);
    }
    
    // Send to Render.com API
    $ch = curl_init($RENDER_API_URL . '/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['messages' => $messagesToSend]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minute timeout
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Log the attempt
    $logMessage = sprintf("[%s] Sent %d messages to Render. HTTP: %d\n", 
        date('Y-m-d H:i:s'), count($messagesToSend), $httpCode);
    file_put_contents(__DIR__ . '/whatsapp_render_log.txt', $logMessage, FILE_APPEND);
    
    if ($httpCode == 200 && $response) {
        $result = json_decode($response, true);
        sendResponse([
            'status' => 'completed',
            'sent' => $result['sent'] ?? 0,
            'failed' => $result['failed'] ?? 0,
            'total' => count($messagesToSend),
            'message' => 'Messages sent successfully via Render bot'
        ]);
    } else {
        sendResponse([
            'status' => 'error',
            'queued' => 0,
            'message' => 'Failed to connect to WhatsApp bot. Error: ' . ($curlError ?: 'HTTP ' . $httpCode),
            'debug' => ['url' => $RENDER_API_URL, 'http_code' => $httpCode]
        ]);
    }
    exit;
}

// ==================== CHECK STATUS ====================
if ($action == 'check_status') {
    $ch = curl_init($RENDER_API_URL . '/status');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $status = json_decode($response, true);
        sendResponse([
            'status' => $status['ready'] ? 'ready' : 'waiting_qr',
            'ready' => $status['ready'] ?? false,
            'qr_available' => isset($status['qr']),
            'message' => $status['ready'] ? 'Bot is ready to send messages' : 'Please scan QR code first'
        ]);
    } else {
        sendResponse([
            'status' => 'offline',
            'ready' => false,
            'message' => 'Render bot is offline or not responding'
        ]);
    }
    exit;
}

// ==================== GET QR CODE (For debugging) ====================
if ($action == 'get_qr') {
    $ch = curl_init($RENDER_API_URL . '/status');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $status = json_decode($response, true);
        sendResponse([
            'qr' => $status['qr'] ?? null,
            'ready' => $status['ready'] ?? false
        ]);
    } else {
        sendResponse(['qr' => null, 'ready' => false]);
    }
    exit;
}

// ==================== TEST CONNECTION ====================
if ($action == 'test_connection') {
    $ch = curl_init($RENDER_API_URL . '/health');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    sendResponse([
        'connected' => ($httpCode == 200),
        'http_code' => $httpCode,
        'response' => $response ? json_decode($response, true) : null,
        'render_url' => $RENDER_API_URL
    ]);
    exit;
}

sendResponse(['success' => false, 'message' => 'Invalid action. Use: send_bulk, check_status, get_qr, or test_connection']);
?>