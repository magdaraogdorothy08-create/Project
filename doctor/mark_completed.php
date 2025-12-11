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
    // 1. Robust Include Paths
    if (!file_exists(__DIR__ . '/../includes/db_connect.php')) {
        throw new Exception('Database connection file not found.');
    }
    include(__DIR__ . '/../includes/db_connect.php');

    // 2. Auth Check
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'doctor') {
        throw new Exception('Unauthorized access.');
    }

    // 3. Input Validation
    if (!isset($_POST['appointment_id'])) {
        throw new Exception('Appointment ID missing.');
    }

    $appointment_id = $_POST['appointment_id'];
    $doctor_id = $_SESSION['user_id'];

    // 4. Update Database
    $sql = "UPDATE appointments SET status = 'Completed' WHERE id = ? AND doctor_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("ii", $appointment_id, $doctor_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows >= 0) {
            $response['success'] = true;
        } else {
            throw new Exception('Could not update appointment. It may not exist or does not belong to you.');
        }
    } else {
        throw new Exception('Database execute failed: ' . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>