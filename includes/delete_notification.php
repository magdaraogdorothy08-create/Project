<?php
session_start();
include('db_connect.php');

if (!isset($_SESSION['user_id']) || !isset($_POST['id'])) {
    exit();
}

$notif_id = $_POST['id'];
$user_id = $_SESSION['user_id'];

// Securely delete the notification
$stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $notif_id, $user_id);
$stmt->execute();
?>