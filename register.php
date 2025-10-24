<?php
include('includes/db_connect.php');
session_start();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Check if email already exists
    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $message = "<div class='alert alert-danger'>Email already exists! Please try another.</div>";
    } else {
        $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
        if ($conn->query($sql) === TRUE) {
            $message = "<div class='alert alert-success'>Registration successful! You can now <a href='login.php'>login</a>.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register – CliniCare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<header class="navbar navbar-dark">
  <div class="container">
    <a class="navbar-brand fw-bold text-white" href="index.php">CliniCare – LNU</a>
  </div>
</header>

<div class="container mt-5" style="max-width: 500px;">
  <div class="card shadow-sm">
    <div class="card-header text-center">Create an Account</div>
    <div class="card-body">
      <?php echo $message; ?>
      <form method="POST" action="">
        <div class="mb-3">
          <label>Full Name:</label>
          <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
        </div>
        <div class="mb-3">
          <label>Email:</label>
          <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
        </div>
        <div class="mb-3">
          <label>Password:</label>
          <input type="password" name="password" class="form-control" placeholder="Enter a password" required>
        </div>
        <div class="mb-3">
          <label>Register as:</label>
          <select name="role" class="form-select" required>
            <option value="patient">Patient</option>
            <option value="doctor">Doctor</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary w-100">Sign Up</button>
      </form>
      <div class="text-center mt-3">
        <p>Already have an account? <a href="login.php">Login here</a></p>
      </div>
    </div>
  </div>
</div>

<footer>&copy; <?php echo date("Y"); ?> Leyte Normal University – CliniCare</footer>
</body>
</html>