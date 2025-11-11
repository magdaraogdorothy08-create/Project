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

    // Get the ID from the POST data
    if (!isset($_POST['appointment_id'])) {
        throw new Exception('Appointment ID is missing.');
    }
    $appointment_id = $_POST['appointment_id'];
    $doctor_id = $_SESSION['user_id'];

    // SQL query is secure: it *only* deletes if the ID and doctor_id match
    $sql = "DELETE FROM appointments WHERE id = ? AND doctor_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("ii", $appointment_id, $doctor_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Delete was successful
            $response['success'] = true;
        } else {
            // No rows were deleted (appointment not found or didn't belong to this doctor)
            throw new Exception('Appointment not found or you do not have permission to delete it.');
        }
    } else {
        throw new Exception('Database execute failed: ' . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    // Catch any error and put it into our JSON 'message' field.
    $response['message'] = $e->getMessage();
    if ($e->getMessage() == 'Unauthorized') {
        http_response_code(403);
    } else {
        http_response_code(500);
    }
}

// Always output our valid JSON response
echo json_encode($response);
exit();
?>