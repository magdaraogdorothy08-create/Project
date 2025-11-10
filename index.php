<?php
session_start();
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] == 'doctor') header("Location: doctor/doctor_dashboard.php");
    if ($_SESSION['user_role'] == 'patient') header("Location: patient/patient_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CliniCare | Leyte Normal University</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<header class="navbar navbar-dark">
  <div class="container">
    <span class="navbar-brand fw-bold">CliniCare – LNUs</span>
  </div>
</header>

<div class="container text-center mt-5">
  <h1 class="fw-bold text-primary">Welcome to CliniCare</h1>
  <p class="lead">Follow-Up Check-Up Scheduling and Reminder System<br>for Leyte Normal University</p>
  <a href="login.php" class="btn btn-primary mt-3">Login</a>
  <a href="register.php" class="btn btn-outline-primary mt-3 ms-2">Sign Up</a>
</div>

<footer>&copy; <?php echo date("Y"); ?> Leyte Normal University – CliniCare</footer>
</body>
</html>

