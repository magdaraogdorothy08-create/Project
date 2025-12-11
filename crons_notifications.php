<?php
// This script is designed to be run by a CRON JOB or TASK SCHEDULER every 5-10 minutes.
// e.g., */5 * * * * php /path/to/cron_notifications.php

include(__DIR__ . '/includes/db_connect.php');

if (file_exists(__DIR__ . '/includes/notification_helper.php')) {
    include_once(__DIR__ . '/includes/notification_helper.php');
}

date_default_timezone_set('Asia/Manila'); 

echo "<h3>Running Notification Service... " . date('Y-m-d H:i:s') . "</h3>";

// =========================================================
// TASK 1: 8:00 AM DAILY REMINDER (For Patients)
// =========================================================
$current_hour = date('H'); 

if ($current_hour == '08') {
    $today = date('Y-m-d');
    
    // Added p.student_id to SELECT
    $sql = "SELECT a.id, a.start_date, a.title, p.phone, p.id as patient_id, p.name, p.student_id 
            FROM appointments a 
            JOIN users p ON a.patient_id = p.id 
            WHERE DATE(a.start_date) = ? 
            AND a.reminder_sent_morning = 0
            AND a.status != 'Completed'
            AND a.reschedule_status != 'Pending'";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Update flag immediately
        $conn->query("UPDATE appointments SET reminder_sent_morning = 1 WHERE id = " . $row['id']);
        
        $time = date('g:i A', strtotime($row['start_date']));
        // Added Student ID to message
        $msg = "Good morning {$row['name']} (ID: {$row['student_id']})! You have a follow-up appointment today at $time. - CliniCare LNU";
        
        if (function_exists('sendSMSViaAndroid') && !empty($row['phone'])) {
            sendSMSViaAndroid($row['phone'], $msg);
            echo "Sent 8AM reminder to {$row['name']}<br>";
        }
        
        $inAppMsg = "Reminder: You have an appointment today at $time.";
        $conn->query("INSERT INTO notifications (user_id, message, appointment_id, created_at) VALUES ({$row['patient_id']}, '$inAppMsg', {$row['id']}, NOW())");
    }
} else {
    echo "Skipping 8AM Blast (Current hour: $current_hour)<br>";
}

// =========================================================
// TASK 2: 30-MINUTE REMINDER (For Doctor & Secretary)
// =========================================================

$now = date('Y-m-d H:i:s');
$future35 = date('Y-m-d H:i:s', strtotime('+35 minutes')); 

// Added p.student_id to SELECT
$sql2 = "SELECT a.id, a.start_date, a.title, 
                d.phone as doc_phone, d.id as doc_id, d.name as doc_name,
                p.name as patient_name, p.student_id
         FROM appointments a 
         JOIN users d ON a.doctor_id = d.id 
         JOIN users p ON a.patient_id = p.id
         WHERE a.start_date > '$now' AND a.start_date <= '$future35'
         AND a.reminder_sent_30min = 0
         AND a.status != 'Completed'
         AND a.reschedule_status != 'Pending'";

$result2 = $conn->query($sql2);

while ($row = $result2->fetch_assoc()) {
    $updateSql = "UPDATE appointments SET reminder_sent_30min = 1 WHERE id = " . $row['id'];
    
    if ($conn->query($updateSql) === TRUE) {
        $time = date('g:i A', strtotime($row['start_date']));
        $patientName = $row['patient_name'];
        $studentID = $row['student_id'];
        
        // 1. Notify Doctor
        // Added Student ID
        $docMsg = "Dr. {$row['doc_name']}, you have an appointment with $patientName (ID: $studentID) in 30 minutes ($time).";
        if (function_exists('sendSMSViaAndroid') && !empty($row['doc_phone'])) {
            sendSMSViaAndroid($row['doc_phone'], $docMsg);
            echo "Sent 30min reminder to Doctor<br>";
        }
        $conn->query("INSERT INTO notifications (user_id, message, appointment_id, created_at) VALUES ({$row['doc_id']}, '$docMsg', {$row['id']}, NOW())");

        // 2. Notify Secretary
        $sec_sql = "SELECT id, phone FROM users WHERE role = 'secretary' AND is_active = 1";
        $sec_res = $conn->query($sec_sql);
        
        while ($sec = $sec_res->fetch_assoc()) {
            // Added Student ID (Critical for record retrieval)
            $secMsg = "Alert: Dr. {$row['doc_name']} has a patient ($patientName, ID: $studentID) in 30 mins. Please prepare records.";
            
            if (function_exists('sendSMSViaAndroid') && !empty($sec['phone'])) {
                sendSMSViaAndroid($sec['phone'], $secMsg);
                echo "Sent 30min reminder to Secretary<br>";
            }
            
            $conn->query("INSERT INTO notifications (user_id, message, appointment_id, created_at) VALUES ({$sec['id']}, '$secMsg', {$row['id']}, NOW())");
        }
    }
}

echo "Check complete.";
?>