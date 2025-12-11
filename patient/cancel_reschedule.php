<?php
session_start();
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    if (!file_exists(__DIR__ . '/../includes/db_connect.php')) throw new Exception('DB missing.');
    include(__DIR__ . '/../includes/db_connect.php');

    // Include Notification Helper
    if (file_exists(__DIR__ . '/../includes/notification_helper.php')) {
        include_once(__DIR__ . '/../includes/notification_helper.php');
    }

    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'patient') {
        throw new Exception('Unauthorized.');
    }

    $appointment_id = $_POST['appointment_id'];
    $patient_id = $_SESSION['user_id'];
    $patient_name = $_SESSION['user_name'];

    // 1. Get Appointment Details (Doctor ID & Original Date) BEFORE updating
    $info_stmt = $conn->prepare("SELECT doctor_id, start_date FROM appointments WHERE id = ? AND patient_id = ?");
    $info_stmt->bind_param("ii", $appointment_id, $patient_id);
    $info_stmt->execute();
    $res = $info_stmt->get_result();
    $appt = $res->fetch_assoc();

    if (!$appt) {
        throw new Exception("Appointment not found.");
    }

    $doctor_id = $appt['doctor_id'];
    $original_date_str = date('F j, g:i A', strtotime($appt['start_date']));

    // 2. Perform the Update (Reset Status)
    $sql = "UPDATE appointments 
            SET reschedule_status = 'None', 
                proposed_date = NULL, 
                reschedule_reason = NULL 
            WHERE id = ? AND patient_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $appointment_id, $patient_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Reschedule request cancelled.';

        // 3. Notify Doctor via SMS/App
        if (function_exists('sendNotification')) {
            $msg = "CliniCare: Patient $patient_name has CANCELLED their reschedule request. The appointment remains on $original_date_str.";
            sendNotification($conn, $doctor_id, $msg, "Reschedule Cancelled");
        }

    } else {
        throw new Exception('Database error.');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>