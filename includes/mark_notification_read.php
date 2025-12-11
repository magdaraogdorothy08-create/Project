<?php
session_start();
include('db_connect.php');

if (!isset($_SESSION['user_id']) || !isset($_POST['id'])) {
    exit();
}

$notif_id = $_POST['id'];
$user_id = $_SESSION['user_id'];

// Mark specific notification as read
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $notif_id, $user_id);
$stmt->execute();
?>