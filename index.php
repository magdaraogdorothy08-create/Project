<?php
session_start();
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] == 'doctor') header("Location: doctor/doctor_dashboard.php");
    if ($_SESSION['user_role'] == 'patient') header("Location: patient/patient_dashboard.php");
    if ($_SESSION['user_role'] == 'admin') header("Location: admin/admin_dashboard.php");
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

<body class="d-flex flex-column min-vh-100">

<header class="navbar navbar-dark sticky-top">
  <div class="container d-flex justify-content-between align-items-center h-100">
    
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="#" style="height: 100%; display: flex; align-items: center;">
        <div style="height: 40px; width: 40px; display: flex; align-items: center; justify-content: center;">
            <img src="assets/pictures/logo.png" alt="Logo" style="height: 150%; width: auto; max-height: 48px;">
        </div>
        <span>CliniCare – Leyte Normal University</span>
    </a>

    <span id="realtimeClock" class="text-white small fw-bold"></span>
  </div>
</header>

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
      
      <div class="d-grid gap-2">
        <a href="login.php" class="btn btn-primary btn-lg">Login</a>
        <a href="register.php" class="btn btn-outline-primary">Sign Up</a>
      </div>
    </div>
  </div>
</div>

<footer class="mt-auto">&copy; <?php echo date("Y"); ?> Leyte Normal University – CliniCare</footer>

<script>
function updateClock() {
    var now = new Date();
    var options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
    document.getElementById('realtimeClock').innerText = now.toLocaleString('en-US', options);
}
setInterval(updateClock, 1000);
updateClock();
</script>
</body>
</html>