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
    // Include the auth check
    include(__DIR__ . '/../includes/auth_check.php');
    
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'doctor') {
        throw new Exception('Unauthorized');
    }

    // Include the database connection
    include(__DIR__ . '/../includes/db_connect.php');
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    if (!isset($_POST['patient_id'], $_POST['title'], $_POST['start_date'], $_POST['start_time'])) {
        throw new Exception('Missing required form data.');
    }

    $doctor_id = $_SESSION['user_id'];
    $patient_id = $_POST['patient_id'];
    $title = $_POST['title'];
    $start_date = $_POST['start_date'];
    $start_time = $_POST['start_time'];

    // --- NEW VALIDATION BLOCK ---
    // This checks the time on the server-side
    if ($start_time < '08:00:00' || $start_time > '17:00:00') {
        throw new Exception('Appointments can only be set between 8:00 AM and 5:00 PM.');
    }
    // --- END OF NEW BLOCK ---

    // Combine date and time into a DATETIME format
    $start_datetime = $start_date . ' ' . $start_time;

    $sql = "INSERT INTO appointments (doctor_id, patient_id, title, start_date) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("iiss", $doctor_id, $patient_id, $title, $start_datetime);

    if ($stmt->execute()) {
        $response['success'] = true;
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