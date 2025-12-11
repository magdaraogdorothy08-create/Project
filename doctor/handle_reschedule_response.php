<?php
session_start();
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    include(__DIR__ . '/../includes/db_connect.php');
    if (file_exists(__DIR__ . '/../includes/notification_helper.php')) {
        include_once(__DIR__ . '/../includes/notification_helper.php');
    }

    // Ensure only doctors can access this
    if ($_SESSION['user_role'] != 'doctor') throw new Exception('Unauthorized.');

    $action = $_POST['action']; // 'approve', 'deny', 'doctor_reschedule'
    $appointment_id = $_POST['appointment_id'];
    $doctor_name = $_SESSION['user_name'];

    // Get appointment details to find patient ID
    $info_stmt = $conn->prepare("SELECT patient_id, proposed_date, start_date FROM appointments WHERE id = ?");
    $info_stmt->bind_param("i", $appointment_id);
    $info_stmt->execute();
    $appt = $info_stmt->get_result()->fetch_assoc();
    
    $patient_id = $appt['patient_id'];
    $proposed_date = $appt['proposed_date'];
    $original_date = $appt['start_date'];

    $msg = ""; // Message to send to patient

    if ($action == 'approve') {
        // SWAP: Proposed Date becomes the new Start Date. Clear Proposed.
        $sql = "UPDATE appointments SET start_date = proposed_date, reschedule_status = 'Approved', proposed_date = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();

        $msg = "CliniCare: Your reschedule request to " . date('M j, g:i A', strtotime($proposed_date)) . " has been APPROVED by Dr. $doctor_name.";

    } elseif ($action == 'deny') {
        // DENY: Keep Original Date. Clear Proposed. Set status to Denied.
        $sql = "UPDATE appointments SET reschedule_status = 'Denied', proposed_date = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();

        $msg = "CliniCare: Your reschedule request was DENIED. Your appointment remains on " . date('M j, g:i A', strtotime($original_date)) . ".";

    } elseif ($action == 'doctor_reschedule') {
        // DOCTOR SETS DATE: Update start_date directly with Doctor's input.
        $new_doctor_date = $_POST['new_date'] . ' ' . $_POST['new_time'];

        // --- WEEKEND BLOCK CHECK ---
        if (date('N', strtotime($new_doctor_date)) >= 6) {
            throw new Exception('Appointments cannot be rescheduled to weekends.');
        }
        
        $sql = "UPDATE appointments SET start_date = ?, reschedule_status = 'Approved', proposed_date = NULL, reschedule_reason = 'Doctor Rescheduled' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_doctor_date, $appointment_id);
        $stmt->execute();

        $msg = "CliniCare: Dr. $doctor_name has rescheduled your appointment to " . date('M j, g:i A', strtotime($new_doctor_date)) . ".";
    }

    // Send SMS Notification to Patient
    if (function_exists('sendNotification')) {
        sendNotification($conn, $patient_id, $msg, "Appointment Update");
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>