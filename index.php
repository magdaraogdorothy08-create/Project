<?php
session_start();
// This PHP logic at the top is perfect. It redirects logged-in users.
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CliniCare | Leyte Normal University</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<!-- 
  FIX 1: Apply the same flexbox classes as login.php 
  to make the sticky footer work.
-->
<body class="d-flex flex-column min-vh-100">
<header class="navbar navbar-dark">
  <div class="container">
    <span class="navbar-brand fw-bold">CliniCare – LNU</span>
  </div>
</header>

<!-- 
  FIX 2: Replaced the old <div> with the centered card layout 
  from login.php. This will make it look modern and match your theme.
-->
<div class="container flex-grow-1 d-flex align-items-center" style="max-width: 550px;">
  <div class="card shadow-sm w-100">
    <div class="card-header text-center fs-5">
      CliniCare – Leyte Normal University
    </div>
    <div class="card-body text-center p-4 p-md-5">
      <h3 class="fw-bold text-primary mb-3">Welcome to CliniCare</h3>
      <p class="lead mb-4">
        Your new Follow-Up Check-Up Scheduling and Reminder System.
      </p>
      
      <!-- This d-grid creates modern, full-width, stacked buttons -->
      <div class="d-grid gap-2">
        <a href="login.php" class="btn btn-primary btn-lg">Login</a>
        <a href="register.php" class="btn btn-outline-primary">Sign Up</a>
      </div>
    </div>
  </div>
</div>

<!-- 
  FIX 3: Added "mt-auto" to the footer to push it 
  to the bottom of the page.
-->
<footer class="mt-auto">&copy; <?php echo date("Y"); ?> Leyte Normal University – CliniCare</footer>
</body>
</html>