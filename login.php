<?php
session_start();
include('includes/db_connect.php');
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];

            if ($user['role'] == 'doctor') {
                header("Location: doctor/doctor_dashboard.php");
            } else {
                header("Location: patient/patient_dashboard.php");
            }
            exit();
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

<header class="navbar navbar-dark">
  <div class="container">
    <a class="navbar-brand fw-bold text-white" href="index.php">CliniCare – LNU</a>
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

</body>
</html>