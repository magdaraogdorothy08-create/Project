<?php
session_start();
header('Content-Type: application/json');

include('db_connect.php');

if (!isset($_SESSION['user_id']) || !isset($_POST['ids'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$user_id = $_SESSION['user_id'];
$ids_raw = $_POST['ids'];

// Ensure IDs is an array
$ids = json_decode($ids_raw);

if (!is_array($ids) || empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No items selected']);
    exit();
}

// Prepare statement for secure deletion
$stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");

$deleted_count = 0;
foreach ($ids as $id) {
    $stmt->bind_param("ii", $id, $user_id);
    if ($stmt->execute()) {
        $deleted_count++;
    }
}

echo json_encode(['success' => true, 'count' => $deleted_count]);
?>