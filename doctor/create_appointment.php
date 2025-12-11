<?php
// MUST be the very first line
session_start(); 

// SET THE HEADER *BEFORE* ANYTHING ELSE
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

try {
    include(__DIR__ . '/../includes/auth_check.php');
    
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'doctor') {
        throw new Exception('Unauthorized');
    }

    include(__DIR__ . '/../includes/db_connect.php');
    
    if (file_exists(__DIR__ . '/../includes/notification_helper.php')) {
        include_once(__DIR__ . '/../includes/notification_helper.php');
    }
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    if (!isset($_POST['patient_id'], $_POST['title'], $_POST['start_date'], $_POST['start_time'])) {
        throw new Exception('Missing required form data.');
    }

    $doctor_id = $_SESSION['user_id'];
    $doctor_name = $_SESSION['user_name'];
    $patient_id = $_POST['patient_id'];
    $title = $_POST['title'];
    $start_date = $_POST['start_date'];
    $start_time = $_POST['start_time'];

    // --- WEEKEND BLOCK CHECK ---
    // 'N' returns 1 (Mon) through 7 (Sun). If >= 6, it's a weekend.
    if (date('N', strtotime($start_date)) >= 6) {
        throw new Exception('Appointments cannot be scheduled on weekends.');
    }

    // Fetch Patient's Student ID
    $pStmt = $conn->prepare("SELECT student_id FROM users WHERE id = ?");
    $pStmt->bind_param("i", $patient_id);
    $pStmt->execute();
    $pRes = $pStmt->get_result();
    $pRow = $pRes->fetch_assoc();
    $student_id_display = $pRow ? $pRow['student_id'] : 'N/A';

    if ($start_time < '08:00' || $start_time > '17:00') {
        throw new Exception('Appointments can only be set between 8:00 AM and 5:00 PM.');
    }
    
    $start_datetime = $start_date . ' ' . $start_time;

    $sql = "INSERT INTO appointments (doctor_id, patient_id, title, start_date) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("iiss", $doctor_id, $patient_id, $title, $start_datetime);

    if ($stmt->execute()) {
        $response['success'] = true;
        $new_appt_id = $stmt->insert_id;

        if (function_exists('sendNotification')) {
            $date_str = date('F j, Y g:i A', strtotime($start_datetime));
            // Added Student ID to message for consistency
            $msg = "CliniCare: Dr. $doctor_name has set an appointment for you (ID: $student_id_display) on $date_str. Reason: $title";
            sendNotification($conn, $patient_id, $msg, "New Appointment Set", $new_appt_id);
        }

    } else {
        throw new Exception('Database execute failed: ' . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    if ($e->getMessage() == 'Unauthorized') {
        http_response_code(403);
    } else {
        http_response_code(500);
    }
}

echo json_encode($response);
exit();
?>