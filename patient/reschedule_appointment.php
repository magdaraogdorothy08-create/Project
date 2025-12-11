<?php
session_start();
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    if (!file_exists(__DIR__ . '/../includes/db_connect.php')) throw new Exception('DB missing.');
    include(__DIR__ . '/../includes/db_connect.php');
    
    if (file_exists(__DIR__ . '/../includes/notification_helper.php')) {
        include_once(__DIR__ . '/../includes/notification_helper.php');
    }

    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'patient') {
        throw new Exception('Unauthorized.');
    }

    if (!isset($_POST['appointment_id'], $_POST['new_start_date'], $_POST['reason'])) {
        throw new Exception('Missing data.');
    }

    $appointment_id = $_POST['appointment_id'];
    $raw_date = $_POST['new_start_date'];
    $reason = $_POST['reason'];
    $patient_id = $_SESSION['user_id'];
    
    // --- WEEKEND BLOCK CHECK ---
    // 'N' returns 1 (Mon) through 7 (Sun). If >= 6, it's a weekend.
    if (date('N', strtotime($raw_date)) >= 6) {
        throw new Exception('Appointments cannot be rescheduled to weekends.');
    }

    // Fetch Patient Name AND Student ID
    $uStmt = $conn->prepare("SELECT name, student_id FROM users WHERE id = ?");
    $uStmt->bind_param("i", $patient_id);
    $uStmt->execute();
    $uRow = $uStmt->get_result()->fetch_assoc();
    $patient_name = $uRow['name'];
    $student_id_display = $uRow['student_id'];

    $new_start_date = date('Y-m-d H:i:s', strtotime($raw_date));

    if (strtotime($new_start_date) < time()) {
        throw new Exception('Cannot reschedule to the past.');
    }

    // Get Doctor ID
    $doc_stmt = $conn->prepare("SELECT doctor_id FROM appointments WHERE id = ?");
    $doc_stmt->bind_param("i", $appointment_id);
    $doc_stmt->execute();
    $res = $doc_stmt->get_result();
    $row = $res->fetch_assoc();
    
    if (!$row) throw new Exception("Appointment not found.");
    $doctor_id = $row['doctor_id'];

    // Check Conflicts
    $check_sql = "SELECT id FROM appointments WHERE doctor_id = ? AND start_date = ? AND id != ?"; 
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("isi", $doctor_id, $new_start_date, $appointment_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        throw new Exception("Time slot already taken.");
    }

    $sql = "UPDATE appointments SET proposed_date = ?, reschedule_reason = ?, reschedule_status = 'Pending' WHERE id = ? AND patient_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $new_start_date, $reason, $appointment_id, $patient_id);

    if ($stmt->execute()) {
        $response['success'] = true;

        if (function_exists('sendNotification')) {
            $date_str = date('M j, g:i A', strtotime($new_start_date));
            // Added Student ID to SMS
            $msg = "Reschedule Request: Patient $patient_name (ID: $student_id_display) wants to move to $date_str. Reason: $reason. Please Approve or Deny in Dashboard.";
            sendNotification($conn, $doctor_id, $msg, "Reschedule Request", $appointment_id);
        }
    } else {
        throw new Exception('Update failed.');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>