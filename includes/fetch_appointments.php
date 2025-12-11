<?php
session_start();
header('Content-Type: application/json');

$response = ['success' => false, 'events' => [], 'error' => ''];

try {
    include(__DIR__ . '/auth_check.php');
    if (!isset($_SESSION['user_id'])) throw new Exception('User not logged in.');

    include(__DIR__ . '/db_connect.php');

    $events = [];
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];

    // --- UPDATED QUERY LOGIC ---
    // We now join the users table TWICE: once for Patient (p), once for Doctor (d)
    
    if ($user_role == 'doctor' || $user_role == 'secretary') {
        $sql = "SELECT a.id, a.title, a.start_date, a.status, a.reschedule_status, a.proposed_date, a.reschedule_reason, 
                       p.name as patient_name, d.name as doctor_name 
                FROM appointments a 
                JOIN users p ON a.patient_id = p.id 
                JOIN users d ON a.doctor_id = d.id";
                
        if ($user_role == 'doctor') {
            $sql .= " WHERE a.doctor_id = ?";
        }
    } 
    else {
        // Patient View
        $sql = "SELECT a.id, a.title, a.start_date, a.status, a.reschedule_status, a.proposed_date, a.reschedule_reason, 
                       d.name as doctor_name 
                FROM appointments a 
                JOIN users d ON a.doctor_id = d.id 
                WHERE a.patient_id = ?";
    }

    $stmt = $conn->prepare($sql);
    
    if ($user_role == 'doctor' || $user_role == 'patient') {
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()) {
        $status = $row['status'];
        $resched_status = $row['reschedule_status']; 
        
        $color = '#002366'; 
        $textColor = '#FFFFFF';
        
        if ($resched_status == 'Pending') {
            $color = '#ffc107'; 
            $textColor = '#000000'; 
            $status = "Waiting for Approval";
        } elseif ($status == 'Completed') {
            $color = '#198754'; 
        }

        // Get names safely
        $p_name = isset($row['patient_name']) ? $row['patient_name'] : '';
        $d_name = isset($row['doctor_name']) ? $row['doctor_name'] : '';
        
        // Title formatting for Calendar
        if ($user_role == 'secretary') {
            // Secretary sees: "Patient Name (Dr. Doctor Name)"
            $event_title = "$p_name (Dr. $d_name)";
        } elseif ($user_role == 'doctor') {
            $event_title = $p_name;
        } else {
            $event_title = "Dr. $d_name";
        }

        if ($resched_status == 'Pending') {
            $event_title = "⏳ " . $event_title;
        }

        $events[] = [
            'id' => $row['id'],
            'title' => $event_title, 
            'start' => $row['start_date'], 
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => $textColor, 
            'display' => 'block',
            'extendedProps' => [
                'status' => $status,
                'resched_status' => $resched_status,
                'proposed_date' => $row['proposed_date'],
                'reschedule_reason' => $row['reschedule_reason'],
                'patient_name' => $p_name,
                'doctor_name' => $d_name // Pass doctor name to frontend
            ]
        ];
    }
    
    $response['success'] = true;
    $response['events'] = $events;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>