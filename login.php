<?php
session_start();
include('includes/db_connect.php');
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Also select student_id
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            
            if ($user['is_active'] == 0) {
                $error = "Your account has been deactivated. Please contact the administrator.";
            } 
            else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                // Store Student ID in session
                $_SESSION['student_id'] = $user['student_id']; 

                if ($user['role'] == 'admin') {
                    header("Location: admin/admin_dashboard.php");
                } elseif ($user['role'] == 'doctor') {
                    header("Location: doctor/doctor_dashboard.php");
                } elseif ($user['role'] == 'secretary') {
                    header("Location: secretary/secretary_dashboard.php");
                } else {
                    header("Location: patient/patient_dashboard.php");
                }
                exit();
            }
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "Invalid email or password!";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login – CliniCare</title>
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

<div class="container flex-grow-1 d-flex align-items-center" style="max-width:400px;">
  <div class="card shadow-sm w-100">
    <div class="card-header text-center">Login to Your Account</div>
    <div class="card-body">
      <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
      <form method="POST">
        <div class="mb-3"><label>Email:</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3"><label>Password:</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>
      <div class="text-center mt-3">
        <p>Don’t have an account? <a href="register.php">Sign Up</a></p>
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