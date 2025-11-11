<?php
// MUST be the very first line
session_start(); 

// SET THE HEADER *BEFORE* ANYTHING ELSE
header('Content-Type: application/json');

$response = [
    'success' => false,
    'patients' => [],
    'error' => ''
];

try {
    // Include the auth check *after* starting the session
    include(__DIR__ . '/../includes/auth_check.php');
    
    // auth_check.php exits if session is invalid, but we'll double-check
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'doctor') {
        throw new Exception('Unauthorized');
    }

    include(__DIR__ . '/../includes/db_connect.php');
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    $sql = "SELECT id, name FROM users WHERE role = 'patient' ORDER BY name";
    $result = $conn->query($sql);

    if ($result) {
        $patients = [];
        while($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
        $response['success'] = true;
        $response['patients'] = $patients;
    } else {
        throw new Exception('Database query failed: ' . $conn->error);
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    if ($e->getMessage() == 'Unauthorized') {
        http_response_code(403);
    } else {
        http_response_code(500);
    }
}

echo json_encode($response);
exit();
?>