<?php
/**
 * Helper to send In-App and SMS notifications
 * Updated for capcom6/android-sms-gateway
 */
function sendNotification($conn, $user_id, $message, $subject) {
    // ---------------------------------------------------------
    // 1. In-App Notification (Database)
    // ---------------------------------------------------------
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        $stmt->close();
    }

    // ---------------------------------------------------------
    // 2. Fetch User's Contact Info (Phone Only)
    // ---------------------------------------------------------
    $contact_stmt = $conn->prepare("SELECT phone FROM users WHERE id = ?");
    if ($contact_stmt) {
        $contact_stmt->bind_param("i", $user_id);
        $contact_stmt->execute();
        $res = $contact_stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $to_phone = $row['phone'];

            if (!empty($to_phone)) {
                $clean_message = strip_tags($message);
                sendSMSViaAndroid($to_phone, $clean_message);
            } else {
                $error_msg = date("Y-m-d H:i:s") . " - SMS Warning: User ID $user_id has no phone number.\n";
                file_put_contents(__DIR__ . '/sms_debug.log', $error_msg, FILE_APPEND);
            }
        }
        $contact_stmt->close();
    }
}

/**
 * Sends SMS via Local Android Gateway App (capcom6)
 */
function sendSMSViaAndroid($number, $message) {
    // --- CONFIGURATION ---
    $phone_ip = '192.168.254.122'; 
    $port     = '8080';
    $username = 'sms';
    $password = 'bSPRK9Cw';
    // ---------------------

    // 1. CORRECT URL according to documentation
    $url = "http://$phone_ip:$port/message";

    // 2. CORRECT PAYLOAD according to documentation
    // The app expects "textMessage" object and "phoneNumbers" array
    $data = [
        'textMessage' => [
            'text' => $message
        ],
        'phoneNumbers' => [$number] 
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // Auth
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); 

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);

    curl_close($ch);

    // --- DEBUG LOGGING ---
    $log_entry = date("Y-m-d H:i:s") . " - Target: $number | HTTP: $http_code | Resp: $response | Err: $curl_error\n";
    file_put_contents(__DIR__ . '/sms_debug.log', $log_entry, FILE_APPEND);
}
?>