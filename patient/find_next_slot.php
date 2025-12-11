<?php
session_start();
include('../includes/db_connect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'patient') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$appointment_id = $_POST['appointment_id'];

// 1. Get the appointment details (Doctor AND Original Date)
$stmt = $conn->prepare("SELECT doctor_id, start_date FROM appointments WHERE id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$res = $stmt->get_result();
$current_appt = $res->fetch_assoc();

if (!$current_appt) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found.']);
    exit();
}

$doctor_id = $current_appt['doctor_id'];
$original_date_str = $current_appt['start_date'];

// 2. Get busy slots
$sql = "SELECT start_date FROM appointments WHERE doctor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

$busy_slots = [];
while($row = $result->fetch_assoc()) {
    $busy_slots[] = date('Y-m-d H:i', strtotime($row['start_date']));
}

// --- LOGIC CHANGE: 3 DAY RULE ---

// Calculate date: Original Date + 3 Days
$three_days_after_original = strtotime($original_date_str . ' + 3 days');

// Calculate date: Tomorrow (to ensure we don't book in the past)
$tomorrow = strtotime('tomorrow 08:00:00');

// We start searching from whichever is LATER (further in the future)
// This ensures we respect the "3 days after" rule, but if that date 
// is already in the past (e.g. rescheduling an old missed appointment), 
// we just start searching from tomorrow.
$start_timestamp = max($three_days_after_original, $tomorrow);

// Align to 8:00 AM on that target day
$search_time = strtotime(date('Y-m-d', $start_timestamp) . ' 08:00:00');

// --------------------------------

$found_slot = false;
$suggested_date = '';

// Limit search to next 45 days
for ($days = 0; $days < 45; $days++) {
    // Loop 8:00 AM to 5:00 PM
    for ($hour = 8; $hour < 17; $hour++) {
        for ($minute = 0; $minute < 60; $minute += 30) {
            
            $current_check = mktime($hour, $minute, 0, date("m", $search_time), date("d", $search_time), date("Y", $search_time));
            $formatted_check = date('Y-m-d H:i', $current_check);
            
            // Skip Weekends
            if (date('N', $current_check) >= 6) { 
                break 2; 
            }

            // Check availability
            if (!in_array($formatted_check, $busy_slots)) {
                $suggested_date = $formatted_check;
                $found_slot = true;
                break 3; 
            }
        }
    }
    $search_time = strtotime('+1 day', $search_time);
}

if ($found_slot) {
    echo json_encode([
        'success' => true, 
        'new_date_raw' => $suggested_date,
        'new_date_human' => date('F j, Y, g:i a', strtotime($suggested_date))
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No available slots found in the coming weeks.']);
}
?>