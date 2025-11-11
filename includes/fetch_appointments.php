<?php
// MUST be the very first line
session_start();

// SET THE HEADER *BEFORE* ANYTHING ELSE
header('Content-Type: application/json');

$response = [
    'success' => false,
    'events' => [],
    'error' => ''
];

try {
    include(__DIR__ . '/auth_check.php');

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in.');
    }

    include(__DIR__ . '/db_connect.php');

    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    $events = [];
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];

    if ($user_role == 'doctor') {
        $sql = "SELECT a.id, a.title, a.start_date, u.name as patient_name 
                FROM appointments a 
                JOIN users u ON a.patient_id = u.id 
                WHERE a.doctor_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            $events[] = [
                'id' => $row['id'],
                'title' => $row['title'] . ' (' . $row['patient_name'] . ')',
                'start' => $row['start_date'],
                'backgroundColor' => '#002366',
                'borderColor' => '#002366'
            ];
        }

    } else if ($user_role == 'patient') {
        $sql = "SELECT a.id, a.title, a.start_date, u.name as doctor_name 
                FROM appointments a 
                JOIN users u ON a.doctor_id = u.id 
                WHERE a.patient_id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while($row = $result->fetch_assoc()) {
            // --- THIS BLOCK IS THE CHANGE ---
            // Changed from Gold to Blue for a consistent look
            $events[] = [
                'id' => $row['id'],
                'title' => $row['title'] . ' (with Dr. ' . $row['doctor_name'] . ')',
                'start' => $row['start_date'],
                'backgroundColor' => '#002366', // LNU Blue
                'borderColor' => '#002366', // LNU Blue
                'textColor' => '#FFFFFF' // White text
            ];
        }
    }
    
    $response['success'] = true;
    $response['events'] = $events;

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