<?php
session_start();
include('db_connect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];

// CHANGED: Removed "AND is_read = 0" so we can see Read messages too.
// Added "LIMIT 10" to keep the list clean.
$sql = "SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    // Format date (e.g., "Oct 24, 8:30 AM")
    $row['created_at'] = date('M j, g:i A', strtotime($row['created_at']));
    $notifications[] = $row;
}

echo json_encode($notifications);
?>