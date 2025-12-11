<?php
session_start();
include('../includes/db_connect.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone']; // Added phone
    $password = $_POST['password'];
    $role = $_POST['role'];

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        throw new Exception("Email already exists.");
    }

    $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
    
    // Updated Query to include 'phone'
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("sssss", $name, $email, $phone, $hashed_pw, $role);

    if ($stmt->execute()) {
        $response['success'] = true;
    } else {
        throw new Exception($stmt->error);
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>