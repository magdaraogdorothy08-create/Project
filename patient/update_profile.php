<?php
session_start();
header('Content-Type: application/json');

include('../includes/db_connect.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'patient') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$phone = $_POST['phone'];
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];

// 1. Verify Current Password
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($current_password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect current password.']);
    exit();
}

// 2. Validate Phone
if (!preg_match('/^09\d{9}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format (09xxxxxxxxx).']);
    exit();
}

// 3. Update Logic
if (!empty($new_password)) {
    if (strlen($new_password) < 6) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 chars.']);
        exit();
    }
    $hashed_pw = password_hash($new_password, PASSWORD_DEFAULT);
    $update_stmt = $conn->prepare("UPDATE users SET phone = ?, password = ? WHERE id = ?");
    $update_stmt->bind_param("ssi", $phone, $hashed_pw, $user_id);
} else {
    $update_stmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
    $update_stmt->bind_param("si", $phone, $user_id);
}

if ($update_stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>